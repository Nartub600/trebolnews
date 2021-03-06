<?php

class ImagenController extends BaseController {

    public function guardar() {
        $data = Input::all();

        $rules = array(
            'imagen' => 'required_without:id',
        );

        $messages = array(
            'imagen.required_without' => 'Es necesario seleccionar una imagen',
        );

        $validator = Validator::make($data, $rules, $messages);

        if($validator->passes()) {
            if(Input::has('id')) {
                $imagen = Imagen::find(Input::get('id'));
                $imagen->nombre = Input::get('nombre');
                $imagen->save();
            } else {
                $nombre = time() . '.' . Input::file('imagen')->getClientOriginalExtension();
                $imagen = Input::file('imagen')->move(public_path() . '/uploads/imagenes', $nombre);

                $imagen = Imagen::create(array(
                    'id_carpeta' => Input::get('id_carpeta') ?: Auth::user()->carpeta_mis_imagenes()->id,
                    'nombre'     => 'Nombre default',
                    'archivo'    => 'uploads/imagenes/' . $nombre
                ));
            }

            return Response::json(array(
                'status' => 'ok'
            ));
        } else {
            return Response::json(array(
                'status'    => 'error',
                'validator' => $validator->messages()->toArray()
            ));
        }
    }

    public function mover() {
        $data = Input::all();

        $rules = array(
            'id_carpeta' => 'required'
        );

        $messages = array(
            'id_carpeta.required' => 'Es necesario elegir una carpeta'
        );

        $validator = Validator::make($data, $rules, $messages);

        if($validator->passes()) {
            $id_carpeta = Input::get('id_carpeta');
            foreach(Input::get('chk_imagen') as $id) {
                $imagen = Imagen::find($id);
                $imagen->id_carpeta = $id_carpeta;
                $imagen->save();
            }

            return Response::json(array(
                'status' => 'ok'
            ));
        } else {
            return Response::json(array(
                'status'    => 'error',
                'validator' => $validator->messages()->toArray()
            ));
        }
    }

    public function trash() {
        $carpeta_basura = Auth::user()->carpeta_basura();
        $input = Input::get('chk_imagen');
        if(!is_array($input)) {
            $input = array($input);
        }
        foreach($input as $id) {
            $imagen = Imagen::find($id);
            if($imagen->id_carpeta == $carpeta_basura->id) {
                $imagen->delete();
            } else {
                $imagen->id_carpeta = $carpeta_basura->id;
                $imagen->save();
            }
        }

        return Response::json(array(
            'status'  => 'ok',
            'refresh' => 'yes'
        ));
    }

    public function eliminar() {
        Imagen::destroy(Input::get('id'));
    }

    public function search_bank() {
        $data = Input::all();

        $rules = array(
            'search-term' => 'required'
        );

        $messages = array(
            'search-term.required' => 'Ingrese un término para la búsqueda'
        );

        $validator = Validator::make($data, $rules, $messages);

        if($validator->passes()) {
            Session::put('search-term', Input::get('search-term'));

            $cant = empty(Auth::user()->preferences()->cant_banco) ? 10 : Auth::user()->preferences()->cant_banco;

            $type = empty(Auth::user()->preferences()->banco_view) ? 'grid' : Auth::user()->preferences()->banco_view;
            $view = "banco-$type";

            $imagenes = Carpeta::find(1)->imagenes()->where('nombre', 'like', '%' . Input::get('search-term', Session::get('search-term')) . '%')->paginate($cant);

            $imagenes->setBaseUrl('lista-banco');

            return Response::json(array(
                'status'    => 'ok',
                'html'      => View::make("trebolnews.listas.$view", array(
                    'imagenes' => $imagenes
                ))->render(),
                'paginador' => $imagenes->links('trebolnews/paginador-ajax')->render(),
                'total'     => $imagenes->count(),
                'term'      => Input::get('search-term', Session::get('search-term'))
            ));
        } else {
            return Response::json(array(
                'status'    => 'error',
                'validator' => $validator->messages()->toArray()
            ));
        }
    }

    public function guardar_interna() {
        $data = Input::all();

        $rules = array(
            'nombre'    => 'required',
            'archivo'   => 'required_without:id',
            'id_categoria' => 'required'
        );

        $messages = array(
            'nombre.required'          => 'Falta ingresar el nombre',
            'archivo.required_without' => 'Falta elegir un archivo',
            'id_categoria.required'       => 'Hay que elegir una categoría'
        );

        $validator = Validator::make($data, $rules, $messages);

        if($validator->passes()) {
            if(Input::has('id')) {
                $imagen = Imagen::find(Input::get('id'));
            } else {
                $imagen = Imagen::create(array(
                    'id_carpeta' => 1
                ));
            }

            $imagen->nombre       = Input::get('nombre');
            $imagen->id_categoria = Input::get('id_categoria');

            if(Input::hasFile('archivo')) {
                $ruta = public_path() . '/img/libreria';
                $nombre = 'libreriaimg' . $imagen->id . '.' . Input::file('archivo')->getClientOriginalExtension();
                Input::file('archivo')->move($ruta, $nombre);
                $imagen->archivo = 'img/libreria/' . $nombre;
            }

            $imagen->save();

            return Response::json(array(
                'status' => 'ok',
                'route'  => route('admin/libreria')
            ));
        } else {
            return Response::json(array(
                'status'    => 'error',
                'validator' => $validator->messages()->toArray()
            ));
        }
    }

    public function eliminar_interna($id) {
        Imagen::destroy($id);

        return Redirect::back();
    }

    public function subir_a_libreria() {
        foreach(Input::get('chk_imagen') as $id_imagen) {
            $imagen = Imagen::find($id_imagen);

            Imagen::create(array(
                'id_carpeta' => Auth::user()->carpeta_mis_imagenes()->id,
                'nombre'     => $imagen->nombre,
                'archivo'    => $imagen->archivo
            ));
        }

        return Response::json(array(
            'route' => route('librerias')
        ));
    }

}
