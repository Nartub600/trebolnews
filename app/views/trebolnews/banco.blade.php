@extends('trebolnews/master')

@section('title')

    TrebolNEWS / Banco de Im&aacute;genes

@stop

@section('data')

    @parent
    <input type="hidden" id="menu_principal" value="librerias" />

@stop

@section('head')

    {{ HTML::style('trebolnews/fancybox/jquery.fancybox.css') }}
    <style>
    .fancybox-nav {
        width: 60px;
    }

    .fancybox-nav span {
        visibility: visible;
        opacity: 0.5;
    }

    .fancybox-next {
        right: -60px;
    }

    .fancybox-prev {
        left: -60px;
    }
    </style>

    {{ HTML::script('trebolnews/fancybox/jquery.fancybox.pack.js') }}
    <script>
    $(function(){

        $('#txt_resultados').hide();

        $('[rel="gallery"]').fancybox({
            padding: 5,
            margin: [20, 60, 20, 60],
            helpers: {
                overlay: {
                    locked: false
                }
            }
        });

        $('#table').on('click', '.banco_ver', function(e){
            e.preventDefault();

            $(this).parents('table').first().find('[rel=gallery]').trigger('click');
        });

        $('#table').on('click', '.checkbox', function(e){
            e.preventDefault();

            var clicked = $(this).find('input');

            if(clicked.hasClass('todos')) {
                if(clicked.prop('checked') == true) {
                    $('.checkbox').find('input').prop('checked', false);
                } else {
                    $('.checkbox').find('input').prop('checked', true);
                }
            } else {
                if(clicked.prop('checked') == true) {
                    clicked.prop('checked', false);
                } else {
                    clicked.prop('checked', true);
                }
            }

            $('#span_seleccionados').text($('.checkbox input:checked').not('.todos').length);

            if($('.checkbox input:checked').not('.todos').length == $('.checkbox input').not('.todos').length) {
                $('.checkbox .todos').prop('checked', true);
            } else {
                $('.checkbox .todos').prop('checked', false);
            }
        });

        $('#btn-subir-a-libreria').one('click', subir_handler);

        function subir_handler(e) {
            e.preventDefault();

            $('#btn-subir-a-libreria').on('click', function(e){
                e.preventDefault();
            });

            $('#frm-subir-a-libreria').append($('.checkbox input:checked').clone()).ajaxSubmit({
                success: function(data) {
                    window.location = data.route;
                },
                complete: function() {
                    $('#frm-subir-a-libreria').html('');
                    $('#btn-subir-a-libreria').one('click', subir_handler);
                }
            });
        }

        $('[name="search-term"]').on('keypress', function(e){
            if(e.which == 13) {
                e.preventDefault();

                $('#frm-search').ajaxSubmit({
                    success: function(data) {
                        if(data.status == 'ok') {
                            $('#table').html(data.html);
                            $('#paginador').html(data.paginador);
                            $('#txt_term').text(data.term);
                            $('#txt_resultados').show();
                        } else {
                            notys(data.validator);
                        }
                    }
                });
            }
        });

    });
    </script>

@stop

@section('contenido')

    <div id="container">
        <section class="tabs">
            <div class="content">
                <h2>Banco de Im&aacute;genes</h2>
                <a id="volver" href="{{ URL::previous() }}"><img src="{{ asset('internas/imagenes/iconovolver.png') }}" alt="volver" width="26" height="26"></a>
                <div class="infocont">
                    <div class="submenu">
                        <ul id="filtrocategoria">
                            <li>
                                <a class="filtro" href="#">CATEGOR&Iacute;AS</a>
                                <ul>
                                    @foreach($categorias as $categoria)
                                    <li><a href="#">{{ Str::title($categoria->descripcion) }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                        </ul>
                        <form id="frm-search" method="post" action="{{ action('ImagenController@search_bank') }}">
                            <input class="search" type="text" placeholder="BUSCAR" name="search-term" />
                        </form>
                        <ul id="subiralibreria_banco">
                            <li>
                                <a href="#" id="btn-subir-a-libreria">SUBIR A LIBRER&Iacute;A</a>
                                <div style="display: none;">
                                    <form id="frm-subir-a-libreria" method="post" action="{{ action('ImagenController@subir_a_libreria') }}">
                                    </form>
                                </div>
                            </li>
                        </ul>
                        <div class="cleaner"></div>
                    </div><!--submenu-->
                    <div id="banco">
                        <div id="submenulibreria">
                            <ul id="filtroselecionados">
                                <li><p id="txt_resultados">Resultados de <em id="txt_term">&#8220;Lorem ipsum&#8221;</em></p></li>
                            </ul>
                            <ul id="filtrover">
                                <li><a id="filtroiconlinsta" href="#" {{ $type == 'list' ? 'class="apretado"' : '' }} preference="banco_view.list" ><img src="{{ asset('internas/imagenes/filtroiconlinsta.png') }}" width="25" height="25"></a></li>
                                <li><a id="filtroiconimagen" href="#" {{ $type == 'grid' ? 'class="apretado"' : '' }} preference="banco_view.grid" ><img src="{{ asset('internas/imagenes/filtroiconimagen.png') }}" width="25" height="25"></a></li>
                            </ul>
                            <ul id="cantidad">
                                <li><a href="#" class="boton">VER</a>
                                <ul>
                                    <li><a href="#" preference="cant_banco.10">10</a></li>
                                    <li><a href="#" preference="cant_banco.20">20</a></li>
                                    <li><a href="#" preference="cant_banco.50">50</a></li>
                                    <li><a href="#" preference="cant_banco.100">100</a></li>
                                </ul>
                                </li>
                            </ul>
                            <div class="cleaner"></div>
                        </div><!-- submenulibreria -->
                        <div id="table">
                            {{ $html }}
                        </div>
                        <div class="cleaner"></div>
                    </div><!--banco -->
                    <div id="paginador">
                        @if(count($imagenes) > 0)
                            {{ $paginador }}
                        @endif
                    </div><!--paginador-->
                    <div class="cleaner"></div>
                </div> <!--infocont-->
            </div>
        </section>
    </div><!--conteiner-->

@stop
