@extends('trebolnews/master')

@section('title')

    TrebolNEWS / Editor

@stop

@section('script')
	{{ HTML::script('ckeditor/ckeditor.js') }}
	{{ HTML::script('ckeditor/adapters/jquery.js') }}
    {{ HTML::script('js/sections/campaigns.js') }}
@stop

@section('contenido')
    <div id="container" class="step4 gallery">


	<section class="tabs">

		<div class="content">
			<h2>Galería de Templates</h2>
			<a id="volver" href="{{ route('step3') }}"><img src="{{ asset('internas/imagenes/iconovolver.png') }}" alt="volver" width="26" height="26"></a>

			<div class="infocont">

				<ul id="pasoscam">
					<li id="pasocam1">Paso 1<div class="linea"></div><div class="circulo"></div></li>
					<li class="separadorpasos"> <div class="separadorlinea"></div></li>
					<li id="pasocam2">Paso 2<div class="linea"></div><div class="circulo"></div></li>
					<li class="separadorpasos"> <div class="separadorlinea"></div></li>
					<li id="pasocam3">Paso 3<div class="linea"></div><div class="circulo"></div></li>
					<li class="separadorpasos"> <div class="separadorlinea"></div></li>
					<li id="pasocam4">Paso 4<div class="linea"></div><div class="circulo pasosactivado"></div></li>
					<li class="separadorpasos"> <div class="separadorlinea"></div></li>
					<li id="pasocam5">Paso 5<div class="linea"></div><div class="circulo"></div></li>
					<div class="cleaner"></div>
				</ul><!--pasoscam-->



				<div style="border:1px solid #D4D8D9;">
				
					<div id="template-gallery">
						@foreach (Template::all() as $template)
						<a href="#" data-template="{{$template->id}}" class="template" style="display:inline-block">
							<img style="width:100px; height:100px;" src="{{$template->thumbnail}}">
							<div><input type="radio" name="template" />{{$template->name}}</div>
						</a>
						@endforeach
					</div>
				</div>

				<div style="border:1px solid #D4D8D9; height:400px;">
					<form action="{{ action('CampaniaController@guardar_campania') }}" id="frm_campania" method="post" data-step="4">
						<textarea id="txt_campania" name="campania:contenido">{{Session::get('campania.contenido')}}</textarea>
					</form>
				</div>

				<div id="opciones_pasos" class="opciones_paso4">
					<!--<a id="guardarysalir" href="{{ route('campanias') }}" class="btn_guardar" y="salir">GUARDAR Y SALIR</a>-->
					<ul>
						<li><a href="{{ route('step3') }}" id="anterior"  class="btn_guardar" y="seguir" >ANTERIOR</a></li>
						<li><a href="{{ route('step5') }}" id="siguiente" class="btn_guardar" y="seguir">VISUALIZAR</a></li>
					</ul>
					<div class="cleaner"></div>
				</div><!--opciones_pasos-->


			</div><!--infocont-->






		</div><!--content-->
	</section>
</div>
@stop
