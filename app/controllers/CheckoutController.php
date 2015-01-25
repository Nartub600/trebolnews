<?php

class CheckoutController extends BaseController {

    public function index($id) {

        $user=Auth::user();

        $foundPlan = Plan::find($id);
        $plan  = array(
            'title'       => $foundPlan['nombre'],
            'unit_price'  => (float) $foundPlan['precio'],
            'quantity'    => 1,
            'currency_id' => 'USD'
        );

        $order = new Orden;
        $order->id_usuario    = $user->id;
        $order->id_plan       = $foundPlan['id'];
        $order->monto         = $foundPlan['precio'];
        $order->isSuscription = $foundPlan['isSuscription'];
        $order->save();

        $external_reference = $order->id;

       

        $payer = array(
            "name"      => $user->nombre,
            "surname"   => $user->apellido,
            "email"     => $user->email
        );

        //URL de retorno
        $back_urls = array(
            "success"   => "http://104.131.97.126/checkout-success",
            "failiure"  => "http://104.131.97.126/",
            "pending"   => "http://104.131.97.126/"
        );

        $items = [];
        array_push($items, $plan);

        $mp = $this->mlAuth();

        $recurrent = $foundPlan['isSuscription'];

        if($recurrent){
            /****************************
                  RECURRENT PAYMENTS
            ****************************/
            $url = 'https://api.mercadolibre.com/currency_conversions/search?from=USD&to=ARS';

             try{
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);     
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSLVERSION, 3);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json'
                    )
                );

                $json = curl_exec($ch);
                curl_close($ch);

                if($json){
                    Log::info($json);     
                    $currency_conversion = json_decode($json);
                    $message = 'nothing found';
                }else{
                    return curl_error($ch);
                }
            
            }catch(Exception $e){
                Log::info('error on curl...'.$e->getMessage());
                return 'problem on curl';
            }

            $ratio = (float) $currency_conversion->ratio;

            $preapproval_data = array(
                "payer_email"           => $payer['email'],
                "back_url"              => $back_urls['success'],
                "reason"                => $plan['title'],
                "external_reference"    => (string) $external_reference,
                "auto_recurring"        => array(
                    "frequency"             => 1,
                    "frequency_type"        => "months",
                    "transaction_amount"    => number_format ( $plan['unit_price']*$ratio, 2),
                    "currency_id"           => "ARS"
                )
            );

            $preapproval = $mp->create_preapproval_payment ($preapproval_data);

            $result = array(
                'plan'     => $plan,
                'mplink'   => $preapproval['response']
            );
        }else{
            /****************************
                   SINGLE PAYMENT
            ****************************/   

            $preference = array (
                "items"              => $items,
                "payer"              => $payer,
                "back_urls"          => $back_urls,
                "external_reference" => (string) $external_reference
            );

            $preferenceResult = $mp->create_preference($preference);


            
            $result = array(
                'plan'     => $plan,
                'mplink'   => $preferenceResult['response'],
                'test'     => $mp
            );
        }

        return View::make('checkout/checkout_review', $result );
    }

    private function mlAuth(){
        /* IDs MercadoPago */
        $mpClientId = "7444";
        $mpSecretId = "tRJ1ATI3zhw2FMTaH1ncPGtGG162Uwei";

        return new MP($mpClientId,$mpSecretId);
    }


    public function success() {
        return View::make('checkout/checkout_success' );
    }




    public function notifications() {
        $topic          = (string) Input::get('topic','Forced Topic');
        $id             = (string) Input::get('id',null);
        $mp             = $this->mlAuth();
        $access_token   = (string) $mp->get_access_token();

        if(!$id){
            $message = 'No ID found';
        }else{
            
            //'https://api.mercadolibre.com/collections/notifications/identificador-de-la-operación?access_token=tu_access_token'
            $url    = 'https://api.mercadolibre.com/sandbox/collections/notifications/'.$id.'?access_token='.$access_token;        
            
            //try to get payment info from MP
            try{
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);     
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSLVERSION, 3);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json'
                    )
                );

                $json = curl_exec($ch);
                curl_close($ch);

                if($json){
                    Log::info($json);     
                    $payment_info = json_decode($json);
                    $message = 'nothing found';
                }else{
                    return curl_error($ch);
                }
            
            }catch(Exception $e){
                Log::info('error on curl...'.$e->getMessage());
                return 'problem on curl';
            }

            
            try{
                $payment_status = $payment_info->collection->status;

                //checks for external reference / order id
                if (isset($payment_info->collection->external_reference)) {
                    $external_reference = $payment_info->collection->external_reference;
                    $orden = (object) Orden::find( (int) $external_reference );

                    if($orden){
                        $message = $this->managePayment($orden,$payment_status);
                    }else{
                        $message = 'non order found';
                    }

                }else{
                    $message = 'non external reference received';
                }
                
                
            }catch(Exception $e){
                $message = 'error happened: '.$e->getMessage();
                Log::info($message);
            }
        }

        return $message;
    }

    private function managePayment ($orden,$payment_status){
        switch ($payment_status) {
            case 'approved':
                $message = $this->setPayment($orden,$payment_status);
                //gives to the user the purchased product
                $this->updatePurchase($orden);
                $this->sendPaymentEmail($orden);
                break;
            case 'pending':
                $message = $this->setPayment($orden,$payment_status);
                break;
            case 'in_process':
                $message = $this->setPayment($orden,$payment_status);
                break;
            case 'rejected':
                $message = $this->setPayment($orden,$payment_status);
                break;
            case 'refunded':
                $message = $this->setPayment($orden,$payment_status);
                break;
            case 'cancelled':
                $message = $this->setPayment($orden,$payment_status);
                break;
            case 'in_mediation':
                $message = $this->setPayment($orden,$payment_status);
                break;
            case 'charged_back':
                $message = $this->setPayment($orden,$payment_status);
                break;
            default:
                $message = $this->setPayment($orden,$payment_status);
                break;

        }
    }

    private function setPayment($orden,$status){
        Log::info('Setting Payment...');

        $pago = new Pago;
        $pago->id_orden      = $orden->id;
        $pago->id_usuario    = $orden->id_usuario;
        $pago->monto         = (float) $orden->monto;
        $pago->status = $status;
        $pago->save();
        return $pago;
    }

    private function updatePurchase($orden){
        Log::info('Updating Purchase...');

        $plan = (object) Plan::find($orden->id_plan);
        Log::info('Plan Retrieved');
        Log::info($plan);
        $user = (object) Usuario::find(Auth::user()->id);
        Log::info('User Retrieved');
        Log::info($user);
        $user->availableMails = $user->availableMails + $plan->envios;
        Log::info('Available Mails are setted now!');
        $user->suscriptionType = 'member';
        Log::info('Logged user becomes a member!');
        $user->save();
        Log::info('Save changes!');
        return $user;
    }
    
    private function sendPaymentEmail($order){
        Log::info('Sending Payment Email...');

        $plan = (object) Plan::find($order->id_plan);
        $user = (object) Usuario::find(Auth::user()->id);

        Mail::send('emails.payment_confirmation', array(
                'user' => $user,
                'order'=> $order,
                'plan' => $plan
            ), function($mail) use($user) {
                $mail->to($user->email);
                $mail->subject('TrebolNEWS - Confirmación de pago');
                $mail->from('no-responder@trebolnews.com', 'TrebolNEWS');
            });
    }

    public function payments(){
        $user = Auth::user();
        $pagos = Pago::where('id_usuario','=',$user['id'])->get();
        $result= array(
            'pagos' => $pagos
        );
        

        return View::make('checkout/payments', $result );
    }

    public function test(){
        $orden = Orden::find( 1 );
        var_dump($this->updatePurchase($orden,'approved'));
    }
}