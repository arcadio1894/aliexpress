<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Dashboard sin areas -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ config('app.name', 'Sermeind') }} | @yield('title')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin/dist/img/logo_dashboard.ico') }}">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Font Awesome Icons -->
    <meta name="google-site-verification" content="0ti5-pM4JvRkJ2Gwg5tqmsBXep9iU_7hz5LHDCIwFEM" />
    <link rel="stylesheet" href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="{{ asset('admin/plugins/pace-progress/themes/black/pace-theme-flat-top.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/jquery-confirm/jquery-confirm.min.css') }}">

    @yield('styles-plugins')

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">

    <style>
        .dropdown-item.active, .dropdown-item:active{
            background-color: #ffffff !important;
        }

        .btn i {
            width: 1em; /* Ajusta el tamaño según sea necesario */
            height: 1em;
        }

        #body-notifications {
            max-height: 300px; /* Establece la altura máxima del contenedor para activar el scroll */
            overflow: auto;    /* Añade un scroll si el contenido supera la altura máxima */
        }

        /* Sidebar con scroll cómodo en AdminLTE 3 */
        .layout-fixed .main-sidebar .sidebar {
            height: calc(100vh - 57px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        /* Cuando AdminLTE usa OverlayScrollbars */
        .main-sidebar .os-content {
            padding-bottom: 60px !important;
        }

        /* Espacio extra al final del menú */
        .main-sidebar .nav-sidebar {
            padding-bottom: 60px !important;
        }

        /* Compactar un poco las opciones */
        .nav-sidebar .nav-link {
            padding-top: 0.45rem;
            padding-bottom: 0.45rem;
        }
    </style>
    @yield('styles')

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini pace-primary layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link" style="color: red"> Tipo de cambio </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link" style="color: blue" id="tasaCompra"></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link" style="color: green" id="tasaVenta"></a>
            </li>
            {{--<li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link">Contact</a>
            </li>--}}
        </ul>

        <!-- SEARCH FORM -->
        {{--<form class="form-inline ml-3">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>--}}

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Messages Dropdown Menu -->
            {{--<li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-comments"></i>
                    <span class="badge badge-danger navbar-badge">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="{{asset('images/users/'.Auth::user()->image)}}" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Brad Diesel
                                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">Call me whenever you can...</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                </div>
            </li>--}}
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" id="showNotifications">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-danger navbar-badge" id="total_notifications"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header" id="quantity_notifications"></span>
                    <div class="dropdown-divider"></div>
                    <div id="body-notifications">

                    </div>
                    <template id="notification-unread">
                        <div class="dropdown-item" >
                            <p class="text-sm">
                                <i class="fas fa-envelope mr-2 text-danger"></i>
                                <span data-message="message" class="text-danger">Nueva cotizacion creada por Operador fgdfgdfgdfg</span>
                                <span class="float-right text-muted text-sm" data-time>Hace 3 mins</span>
                            </p>
                            <br>
                            <a href="#" style="margin-top: 20px" data-read data-content >
                                <span class="float-left text-sm">Marcar como leído</span>
                            </a>
                            <a href="#" style="margin-top: 20px" data-go>
                                <span class="float-right text-sm">Ir</span>
                            </a>
                        </div>
                    </template>
                    <template id="notification-read">
                        <div class="dropdown-item">
                            <p class="text-sm">
                                <i class="fas fa-envelope mr-2"></i>
                                <span data-message="message">Nueva cotizacion creada por Operador fgdfgdfgdfg</span>
                                <span class="float-right text-muted text-sm" data-time>Hace 3 mins</span>
                            </p>
                            {{--<a href="#" style="margin-top: 20px" data-read>
                                <span class="float-left text-sm">Marcar como leído</span>
                            </a>--}}
                            <br>
                            <a href="#" style="margin-top: 20px" data-go>
                                <span class="float-right text-sm">Ir</span>
                            </a>
                        </div>
                    </template>

                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer" id="read-all">Marcar todos como leídos</a>
                </div>
            </li>
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="{{asset('images/users/'.Auth::user()->image)}}" class="user-image img-circle elevation-2" alt="User Image">
                    <span class="d-none d-md-inline">{{Auth::user()->name}}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <!-- User image -->
                    <li class="user-header bg-primary">
                        <img src="{{asset('images/users/'.Auth::user()->image)}}" class="img-circle elevation-2" alt="User Image">

                        <p>
                            {{ Auth::user()->name }}
                            <small>Member since Nov. 2012</small>
                        </p>
                    </li>
                    <!-- Menu Body -->
                    {{--<li class="user-body">
                    <div class="row">
                        <div class="col-4 text-center">
                            <a href="#">Followers</a>
                        </div>
                        <div class="col-4 text-center">
                            <a href="#">Sales</a>
                        </div>
                        <div class="col-4 text-center">
                            <a href="#">Friends</a>
                        </div>
                    </div>
                        <!-- /.row -->
                    </li>--}}
                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <a href="{{ route('user.profile') }}" class="btn btn-default btn-flat">Perfil</a>
                        <a class="btn btn-default btn-flat float-right" href="{{ route('logout') }}" onclick="event.preventDefault();
                             document.getElementById('logout-form').submit();">
                            <i class="fa fa-power-off"></i>
                            Cerrar sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>

        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" class="brand-link">
            <img src="{{ asset('admin/dist/img/logo_dashboard.png') }}" alt="ERP Logo" class="brand-image img-circle elevation-3"
                 style="opacity: .8">
            <span class="brand-text font-weight-light">{{ config('app.name', 'Sermeind') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{asset('images/users/'.Auth::user()->image)}}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="{{ route('dashboard.principal') }}" class="d-block">Dashboard</a>
                </div>
            </div>

            @can('showStoreWeb_dashboard')
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{asset('images/logo/store-icon.png')}}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="{{ route('store-web.catalog') }}" target="_blank" class="d-block">Tienda Web</a>
                </div>
            </div>
            @endcan
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    @can('enable_puntoVenta')
                    <li class="nav-header">PUNTO DE VENTA</li>
                    <li class="nav-item has-treeview @yield('openPuntoVenta')">
                        <a href="#" class="nav-link @yield('activePuntoVenta')">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>
                                VENTAS
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('create_puntoVenta')
                            <li class="nav-item">
                                <a href="{{ route('puntoVenta.index') }}" class="nav-link @yield('activeCreatePuntoVenta')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear venta V1</p>
                                </a>
                            </li>
                            @endcan
                            @can('createV2_puntoVenta')
                                <li class="nav-item">
                                    <a href="{{ route('puntoVenta.indexV2') }}" class="nav-link @yield('activeCreatePuntoVentaV2')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear venta V2</p>
                                    </a>
                                </li>
                            @endcan
                            @can('createFreeSale_puntoVenta')
                                <li class="nav-item">
                                    <a href="{{ route('freeSale.create') }}" class="nav-link @yield('activeCreateFreeSale')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Venta Libre</p>
                                    </a>
                                </li>
                            @endcan
                            @can('list_puntoVenta')
                            <li class="nav-item">
                                <a href="{{ route('puntoVenta.list') }}" class="nav-link @yield('activeListPuntoVenta')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar ventas</p>
                                </a>
                            </li>
                            @endcan
                            @can('listErrors_puntoVenta')
                                <li class="nav-item">
                                    <a href="{{ route('puntoVenta.listError') }}" class="nav-link @yield('activeListErrorPuntoVenta')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar ventas error</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    {{--@can('enable_caja')
                    <li class="nav-header">CAJA</li>
                    <li class="nav-item has-treeview @yield('openCashRegister')">
                        <a href="#" class="nav-link @yield('activeCashRegister')">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>
                                MODULO DE CAJA
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('showEfectivo_caja')
                            <li class="nav-item">
                                <a href="{{ route('index.cashRegister', 'efectivo') }}" class="nav-link @yield('activeCashRegisterEfectivo')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Caja Efectivo</p>
                                </a>
                            </li>
                            @endcan
                            @can('showYape_caja')
                            <li class="nav-item">
                                <a href="{{ route('index.cashRegister', 'yape') }}" class="nav-link @yield('activeCashRegisterYape')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Caja Yape</p>
                                </a>
                            </li>
                            @endcan
                            @can('showPlin_caja')
                            <li class="nav-item">
                                <a href="{{ route('index.cashRegister', 'plin') }}" class="nav-link @yield('activeCashRegisterPlin')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Caja Plin</p>
                                </a>
                            </li>
                            @endcan
                            @can('showBancario_caja')
                            <li class="nav-item">
                                <a href="{{ route('index.cashRegister', 'bancario') }}" class="nav-link @yield('activeCashRegisterBancario')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Caja Bancario</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan--}}

                    @can('enable_caja')
                        <li class="nav-header">CAJA</li>

                        @can('listCashMovementMy_cashBox')
                        <li class="nav-item">
                            <a href="{{ route('cashMovement.my.index') }}"
                               class="nav-link @yield('activeMyCashMovements')">
                                <i class="nav-icon fas fa-cash-register"></i>
                                <p>MIS MOVIMIENTOS DE CAJA</p>
                            </a>
                        </li>
                        @endcan
                    @endcan

                    @can('enableAdmin_caja')
                        <li class="nav-header">CAJA (ADMIN)</li>
                        @can('listCashMovementAdmin_cashBox')
                        <li class="nav-item">
                            <a href="{{ route('cashMovement.admin.index') }}"
                               class="nav-link @yield('activeAdminCashMovements')">
                                <i class="nav-icon fas fa-clipboard-list"></i>
                                <p>MOVIMIENTOS DE CAJA</p>
                            </a>
                        </li>
                        @endcan
                            @can('showFinancialSummary_cashBox')
                                <li class="nav-item">
                                    <a href="{{ route('financial.summary') }}"
                                       class="nav-link @yield('activeAdminFinancialSummary')">
                                        <i class="nav-icon fas fa-money-bill"></i>
                                        <p>RESUMEN FINANCIERO</p>
                                    </a>
                                </li>
                            @endcan
                    @endcan

                    @can('enable_gananciaDiaria')
                    <li class="nav-item has-treeview @yield('openGanancias')">
                        <a href="#" class="nav-link @yield('activeGanancias')">
                            <i class="nav-icon fas fas fa-coins"></i>
                            <p>
                                GANANCIA DIARIA
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('show_gananciaDiaria')
                            <li class="nav-item">
                                <a href="{{ route('ganancia.index') }}" class="nav-link @yield('activeGananciaDiaria')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ganancia Diaria</p>
                                </a>
                            </li>
                            @endcan
                            @can('showGananciaWorker_gananciaDiaria')
                            <li class="nav-item">
                                <a href="{{ route('ganancia.index.trabajador') }}" class="nav-link @yield('activeGananciaDiariaTrabajador')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ganancia x Trabajador</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('enable_metas')
                    <li class="nav-header">METAS</li>
                    <li class="nav-item has-treeview @yield('openMetas')">
                        <a href="#" class="nav-link @yield('activeMetas')">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>
                                METAS
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_metas')
                            <li class="nav-item">
                                <a href="{{ route('metas.index') }}" class="nav-link @yield('activeMetasListado')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listado de Metas</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_metas')
                            <li class="nav-item">
                                <a href="{{ route('metas.create') }}" class="nav-link @yield('activeMetasCreate')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear Metas</p>
                                </a>
                            </li>
                            @endcan
                            @can('progress_metas')
                            <li class="nav-item">
                                <a href="{{ route('metas.ranking') }}" class="nav-link @yield('activeMetasProgreso')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Progreso de Metas</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @can('enable_promotions')
                    <li class="nav-header">PROMOCIONES</li>
                    <li class="nav-item has-treeview @yield('openPromotions')">

                        <a href="#" class="nav-link @yield('activePromotions')">
                            <i class="nav-icon fas fa-eye-slash"></i>
                            <p>
                                Promociones
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('showSeasonal_promotions')
                                <li class="nav-item">
                                    <a href="{{ route('promotion.seasonal.index') }}" class="nav-link @yield('activePromotionsSeasonal')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Por Temporada</p>
                                    </a>
                                </li>
                            @endcan
                            @can('showCombo_promotions')
                                <li class="nav-item">
                                    <a href="{{ route('index.combos') }}" class="nav-link @yield('activePromotionsCombo')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Por combos</p>
                                    </a>
                                </li>
                            @endcan

                                {{--<li class="nav-item">
                                    <a href="#" class="nav-link @yield('activePromotionsDiscount')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Por descuento</p>
                                    </a>
                                </li>--}}

                            @can('showLimite_promotions')
                            <li class="nav-item">
                                <a href="{{ route('promotionLimit.index') }}" class="nav-link @yield('activePromotionLimit')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Por limites</p>
                                </a>
                            </li>
                            @endcan
                            @can('order_promotions')
                            <li class="nav-item">
                                <a href="{{ route('promotionOrder.index') }}" class="nav-link @yield('activePromotionOrder')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Orden de Promociones</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @canany(['access_permission', 'enable_dataGeneral'])
                    <li class="nav-header">ADMINISTRADOR</li>
                    @endcanany
                    @can('access_permission')
                    <li class="nav-item has-treeview @yield('openAccess')">

                        <a href="#" class="nav-link @yield('activeAccess')">
                            <i class="nav-icon fas fa-eye-slash"></i>
                            <p>
                                Accesos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_permission')
                            <li class="nav-item">
                                <a href="{{ route('permission.index') }}" class="nav-link @yield('activePermissions')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Permisos</p>
                                </a>
                            </li>
                            @endcan
                            @can('list_role')
                            <li class="nav-item">
                                <a href="{{ route('role.index') }}" class="nav-link @yield('activeRoles')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Roles</p>
                                </a>
                            </li>
                            @endcan
                            @can('list_user')
                                <li class="nav-item">
                                    <a href="{{ route('user.index') }}" class="nav-link @yield('activeUser')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Usuarios Activos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.indexEnable') }}" class="nav-link @yield('activeUserEnable')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Usuarios Eliminados</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @can('enable_dataGeneral')
                    <li class="nav-item has-treeview @yield('openDataGeneral')">

                        <a href="#" class="nav-link @yield('activeDataGeneral')">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Datos de Configuración
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_dataGeneral')
                                <li class="nav-item">
                                    <a href="{{ route('dataGeneral.index') }}" class="nav-link @yield('activeListDataGeneral')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Datos Generales</p>
                                    </a>
                                </li>
                            @endcan

                            {{-- NUEVO: Cajas --}}
                            @can('list_cashBox')
                                <li class="nav-item">
                                    <a href="{{ route('cashBox.index') }}" class="nav-link @yield('activeCashBox')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Cajas</p>
                                    </a>
                                </li>
                            @endcan

                            {{-- NUEVO: Subtipos --}}
                            @can('list_cashBoxSubtype')
                                <li class="nav-item">
                                    <a href="{{ route('cashBoxSubtype.index') }}" class="nav-link @yield('activeCashBoxSubtype')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Subtipos bancarios</p>
                                    </a>
                                </li>
                            @endcan

                            @can('listUser_configUserWeb')
                                <li class="nav-item">
                                    <a href="{{ route('configUserWeb.index') }}" class="nav-link @yield('activeConfigUserWeb')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Usuarios Web</p>
                                    </a>
                                </li>
                            @endcan

                        </ul>

                    </li>
                    @endcan
                    @canany(['list_customer', 'list_contactName', 'list_supplier'])
                    <li class="nav-header">MANTENEDORES</li>
                    @endcanany
                    @can('list_customer')
                    <li class="nav-item has-treeview @yield('openCustomer')">

                        <a href="#" class="nav-link @yield('activeCustomer')">
                            <i class="nav-icon fas fa-briefcase"></i>
                            <p>
                                Clientes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_customer')
                            <li class="nav-item">
                                <a href="{{ route('customer.index') }}" class="nav-link @yield('activeListCustomer')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar clientes</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_customer')
                            <li class="nav-item">
                                <a href="{{ route('customer.create') }}" class="nav-link @yield('activeCreateCustomer')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear clientes</p>
                                </a>
                            </li>
                            @endcan
                            @can('destroy_customer')
                            <li class="nav-item">
                                <a href="{{ route('customer.indexrestore') }}" class="nav-link @yield('activeRestoreCustomer')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Restaurar clientes</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @can('list_contactName')
                    <li class="nav-item has-treeview @yield('openContactName')">
                        <a href="#" class="nav-link @yield('activeContactName')">
                            <i class="nav-icon fas fa-address-book"></i>
                            <p>
                                Contactos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_contactName')
                                <li class="nav-item">
                                    <a href="{{ route('contactName.index') }}" class="nav-link @yield('activeListContactName')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar contactos</p>
                                    </a>
                                </li>
                            @endcan
                            @can('create_contactName')
                                <li class="nav-item">
                                    <a href="{{ route('contactName.create') }}" class="nav-link @yield('activeCreateContactName')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear contacto</p>
                                    </a>
                                </li>
                            @endcan
                            @can('destroy_contactName')
                                <li class="nav-item">
                                    <a href="{{ route('contactName.indexrestore') }}" class="nav-link @yield('activeRestoreContactName')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Restaurar contactos</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @can('list_supplier')
                    <li class="nav-item has-treeview @yield('openSupplier')">
                        <a href="#" class="nav-link @yield('activeSupplier')">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                Proveedores
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_supplier')
                            <li class="nav-item">
                                <a href="{{ route('supplier.index') }}" class="nav-link @yield('activeListSupplier')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar proveedores</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_supplier')
                            <li class="nav-item">
                                <a href="{{ route('supplier.create') }}" class="nav-link @yield('activeCreateSupplier')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear proveedores</p>
                                </a>
                            </li>
                            @endcan
                            @can('destroy_supplier')
                                <li class="nav-item">
                                    <a href="{{ route('supplier.indexrestore') }}" class="nav-link @yield('activeRestoreSupplier')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Restaurar proveedores</p>
                                    </a>
                                </li>
                            @endcan
                            {{--@can('assign_supplier')
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Proveedores y materiales</p>
                                </a>
                            </li>
                            @endcan--}}
                        </ul>
                    </li>
                    @endcan

                    @can('enable_paymentDeadline')
                        <li class="nav-header">PLAZOS DE PAGO</li>
                        <li class="nav-item has-treeview @yield('openPaymentDeadline')">
                            <a href="#" class="nav-link @yield('activePaymentDeadline')">
                                <i class="nav-icon fas fa-handshake"></i>
                                <p>
                                    Plazo de pago
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_paymentDeadline')
                                    <li class="nav-item">
                                        <a href="{{route('paymentDeadline.index')}}" class="nav-link @yield('activeListPaymentDeadline')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar plazos</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_paymentDeadline')
                                    <li class="nav-item">
                                        <a href="{{ route('paymentDeadline.create') }}" class="nav-link @yield('activeCreatePaymentDeadline')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear plazos</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @canany(['list_material','list_unitMeasure', 'list_typeScrap', 'list_category', 'list_subcategory', 'list_materialType', 'list_subType', 'list_warrant', 'list_quality', 'list_brand', 'list_exampler'])
                    <li class="nav-header">MATERIALES</li>
                    @endcanany
                    {{--@canany('list_unitMeasure', 'list_typeScrap', 'list_category', 'list_subcategory', 'list_materialType', 'list_subType', 'list_warrant', 'list_quality', 'list_brand', 'list_exampler')--}}
                    @can('configGeneral_materialSetting')
                    <li class="nav-item has-treeview @yield('openConfig')">
                        <a href="#" class="nav-link @yield('activeConfig')">
                            <i class="fas fa-tools nav-icon"></i>
                            <p>
                                Configuraciones
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_unitMeasure')
                                <li class="nav-item has-treeview @yield('openUnitMeasure')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Unidad de Medida
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_unitMeasure')
                                        <li class="nav-item">
                                            <a href="{{ route('unitmeasure.index') }}" class="nav-link @yield('activeListUnitMeasure')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar unidades</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_unitMeasure')
                                        <li class="nav-item">
                                            <a href="{{ route('unitmeasure.create') }}" class="nav-link @yield('activeCreateUnitMeasure')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear unidades</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_typeScrap')
                                <li class="nav-item has-treeview @yield('openTypeScrap')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Tipo de retacería
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_typeScrap')
                                            <li class="nav-item">
                                                <a href="{{ route('typescrap.index') }}" class="nav-link @yield('activeListTypeScrap')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar Tipo retacería</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_typeScrap')
                                            <li class="nav-item">
                                                <a href="{{ route('typescrap.create') }}" class="nav-link @yield('activeCreateTypeScrap')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear Tipo retacería</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_category')
                            <li class="nav-item has-treeview @yield('openCategory')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Categorías
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_category')
                                    <li class="nav-item">
                                        <a href="{{ route('category.index') }}" class="nav-link @yield('activeListCategory')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar categorias</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_category')
                                    <li class="nav-item">
                                        <a href="{{ route('category.create') }}" class="nav-link @yield('activeCreateCategory')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear categorias</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan

                            @can('list_subcategory')
                                <li class="nav-item has-treeview @yield('openSubcategory')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Subcategorías
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_subcategory')
                                        <li class="nav-item">
                                            <a href="{{ route('subcategory.index') }}" class="nav-link @yield('activeListSubcategory')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar subcategorias</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_subcategory')
                                        <li class="nav-item">
                                            <a href="{{ route('subcategory.create') }}" class="nav-link @yield('activeCreateSubcategory')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear subcategorias</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_materialType')
                                <li class="nav-item has-treeview @yield('openMaterialType')">
                                    <a href="#" class="nav-link @yield('activeMaterialType')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Tipo Materiales
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_materialType')
                                        <li class="nav-item">
                                            <a href="{{ route('materialtype.index') }}" class="nav-link @yield('activeListMaterialType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar tipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_materialType')
                                        <li class="nav-item">
                                            <a href="{{ route('materialtype.create') }}" class="nav-link @yield('activeCreateMaterialType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear tipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_subType')
                                <li class="nav-item has-treeview @yield('openSubType')">
                                    <a href="#" class="nav-link @yield('activeSubType')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            SubTipos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_subType')
                                        <li class="nav-item">
                                            <a href="{{ route('subtype.index') }}" class="nav-link @yield('activeListSubType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar Subtipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_subType')
                                        <li class="nav-item">
                                            <a href="{{ route('subtype.create') }}" class="nav-link @yield('activeCreateSubType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear Subtipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            {{--@can('list_warrant')
                                <li class="nav-item has-treeview @yield('openWarrant')">
                                    <a href="#" class="nav-link @yield('activeWarrant')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Género
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_warrant')
                                        <li class="nav-item">
                                            <a href="{{ route('genero.index') }}" class="nav-link @yield('activeListWarrant')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar géneros</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_warrant')
                                        <li class="nav-item">
                                            <a href="{{ route('genero.create') }}" class="nav-link @yield('activeCreateWarrant')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear géneros</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan--}}

                            {{--@can('list_quality')
                                <li class="nav-item has-treeview @yield('openQuality')">
                                    <a href="#" class="nav-link @yield('activeQuality')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Tallas
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_quality')
                                        <li class="nav-item">
                                            <a href="{{ route('talla.index') }}" class="nav-link @yield('activeListQuality')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar tallas</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_quality')
                                        <li class="nav-item">
                                            <a href="{{ route('talla.create') }}" class="nav-link @yield('activeCreateQuality')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear Tallas</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan--}}

                            @can('list_brand')
                            <li class="nav-item has-treeview @yield('openBrand')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Marcas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_brand')
                                    <li class="nav-item">
                                        <a href="{{ route('brand.index') }}" class="nav-link @yield('activeListBrand')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar marcas</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_brand')
                                    <li class="nav-item">
                                        <a href="{{ route('brand.create') }}" class="nav-link @yield('activeCreateBrand')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear marcas</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan

                            @can('list_exampler')
                            <li class="nav-item has-treeview @yield('openExampler')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Modelos
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_exampler')
                                    <li class="nav-item">
                                        <a href="{{ route('exampler.index') }}" class="nav-link @yield('activeListExampler')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar modelos</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_exampler')
                                    <li class="nav-item">
                                        <a href="{{ route('exampler.create') }}" class="nav-link @yield('activeCreateExampler')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear modelos</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan

                            @can('enable_files')
                                <li class="nav-item has-treeview @yield('openListFiles')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Importar archivos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('stock_files')
                                            <li class="nav-item">
                                                <a href="{{ route('stocks.files.index') }}" class="nav-link @yield('activeListStockFiles')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Stocks</p>
                                                </a>
                                            </li>
                                        @endcan

                                    </ul>
                                </li>
                            @endcan

                            @can('enable_materialSetting')
                                <li class="nav-item has-treeview @yield('openSettingsMaterialDetail')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Parámetros
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_exampler')
                                            <li class="nav-item">
                                                <a href="{{ route('settings.material-details.index') }}" class="nav-link @yield('activeSettingsMaterialDetails')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Configurar Detalles</p>
                                                </a>
                                            </li>
                                        @endcan

                                    </ul>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany
                    @can('list_material')
                        <li class="nav-item has-treeview @yield('openMaterial')">
                            <a href="#" class="nav-link @yield('activeMaterial')">
                                <i class="nav-icon fas fa-boxes"></i>
                                <p>
                                    Materiales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_material')
                                {{--<li class="nav-item">
                                    <a href="{{route('material.index.store')}}" class="nav-link @yield('activeListMaterialStore')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar materiales Tienda</p>
                                    </a>
                                </li>--}}
                                <li class="nav-item">
                                    <a href="{{route('material.indexV2')}}" class="nav-link @yield('activeListMaterial')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar materiales Almacen</p>
                                    </a>
                                </li>

                                @endcan
                                {{--@can('listActive_material')
                                    <li class="nav-item">
                                        <a href="{{route('material.actives.index')}}" class="nav-link @yield('activeListMaterialActive')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar activos fijos</p>
                                        </a>
                                    </li>
                                @endcan--}}
                                @can('listStockitem_material')
                                    <li class="nav-item">
                                        <a href="{{route('stockitems.index')}}" class="nav-link @yield('activeListStockItems')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar stocks items</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_material')
                                <li class="nav-item">
                                    <a href="{{ route('material.create') }}" class="nav-link @yield('activeCreateMaterial')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear materiales</p>
                                    </a>
                                </li>
                                @endcan
                                @can('enable_material')
                                    <li class="nav-item">
                                        <a href="{{ route('material.index.enable') }}" class="nav-link @yield('activeEnableMaterial')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Habilitar materiales</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('separetePacks_material')
                                    <li class="nav-item">
                                        <a href="{{ route('material.separate.pack') }}" class="nav-link @yield('activeSeparatePackMaterial')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Separar paquetes</p>
                                        </a>
                                    </li>
                                @endcan
                                    {{--<li class="nav-item">
                                        <a href="{{ route('index.combos') }}" class="nav-link @yield('listComboMaterial')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado de combos</p>
                                        </a>
                                    </li>--}}
                                    {{--<li class="nav-item">
                                        <a href="{{ route('material.generate.combo') }}" class="nav-link @yield('activeGenerateComboMaterial')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Generar combo</p>
                                        </a>
                                    </li>--}}
                            </ul>
                        </li>
                    @endcan

                    @canany(['list_quote'])
                        <li class="nav-header">COTIZACIONES</li>
                    @endcanany
                    @can('list_quote')
                        <li class="nav-item has-treeview @yield('openQuote')">
                            <a href="#" class="nav-link @yield('activeQuote')">
                                <i class="nav-icon fas fa-boxes"></i>
                                <p>
                                    Cotizaciones
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quoteSale.index') }}" class="nav-link @yield('activeGeneralQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado general</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_quote')
                                    <li class="nav-item">
                                        <a href="{{route('quoteSale.index.facturadas')}}" class="nav-link @yield('activeListFacturadasQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar facturadas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quoteSale.create') }}" class="nav-link @yield('activeCreateQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear cotización</p>
                                        </a>
                                    </li>
                                @endcan
                               {{-- @can('showRaised_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.raiseV2') }}" class="nav-link @yield('activeRaiseQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Elevar cotización</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('destroy_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.deleted') }}" class="nav-link @yield('activeDeletedQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Anuladas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('finish_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.closed') }}" class="nav-link @yield('activeClosedQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Finalizadas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.list.lost') }}" class="nav-link @yield('activeLostQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Perdidas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_porcentageQuote')
                                    <li class="nav-item">
                                        <a href="{{ route('porcentageQuote.index') }}" class="nav-link @yield('activePorcentagesQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Porcentajes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('resumen_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('resumen.quote') }}" class="nav-link @yield('activeResumeQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Resumen Cotización</p>
                                        </a>
                                    </li>
                                @endcan--}}
                            </ul>
                        </li>
                    @endcan

                    @can('enableArea_inventory')
                        <li class="nav-header">INVENTARIO</li>
                        <li class="nav-item has-treeview @yield('openInventory')">
                            <a href="#" class="nav-link @yield('activeInventory')">
                                <i class="nav-icon fas fa-book"></i>
                                <p>
                                    Inventario Físico
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_area')
                                <li class="nav-item">
                                    <a href="{{ route('area.index') }}" class="nav-link @yield('activeAreas')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Áreas</p>
                                    </a>
                                </li>
                                @endcan
                                @can('list_location')
                                <li class="nav-item">
                                    <a href="{{ route('location.index') }}" class="nav-link @yield('activeLocations')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ubicaciones</p>
                                    </a>
                                </li>
                                @endcan
                                @can('list_inventory')
                                    <li class="nav-item">
                                        <a href="{{ route('inventory.index') }}" class="nav-link @yield('activeListInventory')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('kardex.index') }}" class="nav-link @yield('activeListInventoryKardex')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Kardex</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>

                        @can('list_transfer')
                            {{--<li class="nav-header">TRANSFERENCIAS</li>--}}
                            <li class="nav-item has-treeview @yield('openTransfer')">
                                <a href="#" class="nav-link @yield('activeTransfer')">
                                    <i class="nav-icon fas fa-retweet"></i>
                                    <p>
                                        Transferencias
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_transfer')
                                        <li class="nav-item">
                                            <a href="{{ route('transfer.index') }}" class="nav-link @yield('activeListTransfer')">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Listar traslados</p>
                                            </a>
                                        </li>
                                    @endcan
                                    @can('create_transfer')
                                        <li class="nav-item">
                                            <a href="{{ route('transfer.create') }}" class="nav-link @yield('activeCreateTransfer')">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Crear traslado</p>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan
                    @endcan

                    @can('enable_referralGuide')
                        <li class="nav-header">GUIAS DE REMISIÓN</li>
                        <li class="nav-item has-treeview @yield('openReferralGuide')">
                            <a href="#" class="nav-link @yield('activeReferralGuide')">
                                <i class="nav-icon fas fa-book"></i>
                                <p>
                                    Mantenedor de guías
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_referralGuide')
                                    <li class="nav-item">
                                        <a href="{{ route('shipping_guides.view') }}" class="nav-link @yield('activeListReferralGuide')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado de guías</p>
                                        </a>
                                    </li>
                                @endcan
                                {{--@can('create_referralGuide')
                                    <li class="nav-item">
                                        <a href="{{ route('referral.guide.create') }}" class="nav-link @yield('activeCreateReferralGuide')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear guía de remisión</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('setManagers_referralGuide')
                                    <li class="nav-item">
                                        <a href="--}}{{--{{ route('inventory.index') }}--}}{{--" class="nav-link @yield('activeListInventory')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Configurar responsables</p>
                                        </a>
                                    </li>
                                @endcan--}}
                            </ul>
                        </li>
                    @endcan

                    @canany(['list_entryPurchase', 'list_entryScrap'])
                    <li class="nav-header">ENTRADAS A ALMACEN</li>
                    @endcanany
                    @can('list_entryPurchase')
                    <li class="nav-item has-treeview @yield('openEntryPurchase')">
                        <a href="#" class="nav-link @yield('activeEntryPurchase')">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>
                                Por compra
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_entryPurchase')
                            <li class="nav-item">
                                <a href="{{ route('entry.purchase.indexV2') }}" class="nav-link @yield('activeListEntryPurchase')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar entradas</p>
                                </a>
                            </li>
                            {{--<li class="nav-item">
                                <a href="{{ route('entry.purchase.index') }}" class="nav-link @yield('activeListEntryPurchase')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar entradas</p>
                                </a>
                            </li>--}}
                            @endcan
                            {{--@can('create_entryPurchase')
                            <li class="nav-item">
                                <a href="{{ route('entry.purchase.create') }}" class="nav-link @yield('activeCreateEntryPurchase')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear entrada</p>
                                </a>
                            </li>
                            @endcan--}}
                            @can('listOrder_entryPurchase')
                            {{--<li class="nav-item">
                                <a href="{{ route('order.purchase.list') }}" class="nav-link @yield('activeListOrdersInEntries')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar órdenes</p>
                                </a>
                            </li>--}}
                            <li class="nav-item">
                                <a href="{{ route('order.purchase.list.indexV2') }}" class="nav-link @yield('activeListOrdersInEntries')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar órdenes</p>
                                </a>
                            </li>
                            @endcan
                            {{--@can('reportMaterialEntries_entryPurchase')
                            <li class="nav-item">
                                <a href="{{ route('report.materials.entries') }}" class="nav-link @yield('activeReportMaterialEntry')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Reporte Ingresos</p>
                                </a>
                            </li>
                            @endcan--}}
                        </ul>
                    </li>
                    @endcan
                    @can('list_entryScrap')
                    <li class="nav-item has-treeview @yield('openEntryScrap')">
                        <a href="#" class="nav-link @yield('activeEntryScrap')">
                            <i class="nav-icon fas fa-archive"></i>
                            <p>
                                Por retazos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_entryScrap')
                            <li class="nav-item">
                                <a href="{{ route('entry.scrap.index') }}" class="nav-link @yield('activeListEntryScrap')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar entradas</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_entryScrap')
                            {{--<li class="nav-item">
                                <a href="{{ route('entry.scrap.create') }}" class="nav-link @yield('activeCreateEntryScrap')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear entrada</p>
                                </a>
                            </li>--}}
                            <li class="nav-item">
                                <a href="{{ route('entry.create.scrap') }}" class="nav-link @yield('activeCreateScrap')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear retazos</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @can('list_entryInventory')
                    <li class="nav-item has-treeview @yield('openEntryInventory')">
                        <a href="#" class="nav-link @yield('activeEntryInventory')">
                            <i class="nav-icon fas fa-archive"></i>
                            <p>
                                Por Inventario
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_entryInventory')
                            <li class="nav-item">
                                <a href="{{ route('entry.inventory.index') }}" class="nav-link @yield('activeListEntryInventory')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar entradas</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('showReportEntries_entryPurchase')
                    <li class="nav-item has-treeview @yield('openEntryPurchaseReport')">
                        <a href="#" class="nav-link @yield('activeEntryPurchaseReport')">
                            <i class="nav-icon fas fa-archive"></i>
                            <p>
                                Reportes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('showReportEntries_entryPurchase')
                            <li class="nav-item">
                                <a href="{{ route('entry.purchase.report.index') }}" class="nav-link @yield('activeListEntryPurchaseReport')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar por Compras</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    {{--@can('showMaterials_orderExecutionAlmacen')
                    <li class="nav-header">MATERIAL EN ORD. EJEC.</li>
                    <li class="nav-item has-treeview @yield('openExecutionsAlmacen')">
                        <a href="#" class="nav-link @yield('activeExecutionsAlmacen')">
                            <i class="nav-icon fas fa-hammer"></i>
                            <p>
                                Órdenes de ejecución
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('listOrders_orderExecutionAlmacen')
                            <li class="nav-item">
                                <a href="{{ route('order.execution.almacen') }}" class="nav-link @yield('activeListExecutionsAlmacen')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar Ordenes</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan--}}
                    {{--@can('list_orderExecution')
                        <li class="nav-header">ORDENES EJECUCIÓN</li>
                        <li class="nav-item has-treeview @yield('openOrderExecutions')">
                            <a href="#" class="nav-link @yield('activeOrderExecutions')">
                                <i class="nav-icon fas fa-hammer"></i>
                                <p>
                                    Orden de ejecución
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('order.execution.index') }}" class="nav-link @yield('activeListOrderExecutions')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar Ordenes</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endcan--}}

                    @can('enable_requestSimple')
                        <li class="nav-header">SOLICITUD DE ÁREA</li>
                        <li class="nav-item has-treeview @yield('openOutputSimple')">
                            <a href="#" class="nav-link @yield('activeOutputSimple')">
                                <i class="nav-icon fas fa-file"></i>
                                <p>
                                    Solicitud de Área
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_requestSimple')
                                    <li class="nav-item">
                                        <a href="{{ route('output.simple.index') }}" class="nav-link @yield('activeListOutputSimple')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar solicitudes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('myRequest_requestSimple')
                                    <li class="nav-item">
                                        <a href="{{ route('output.simple.my.index') }}" class="nav-link @yield('activeListMyOutputSimple')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar mis solicitudes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_requestSimple')
                                    <li class="nav-item">
                                        <a href="{{ route('output.simple.create.activos') }}" class="nav-link @yield('activeCreateOutputSimple')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear solicitudes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('report_requestSimple')
                                    <li class="nav-item">
                                        <a href="{{ route('output.simple.report') }}" class="nav-link @yield('activeReportMaterialOutputSimple')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Materiales en salida</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('list_request')
                    <li class="nav-header">SOLICITUDES</li>
                    <li class="nav-item has-treeview @yield('openOutputRequest')">
                        <a href="#" class="nav-link @yield('activeOutputRequest')">
                            <i class="nav-icon fas fa-file"></i>
                            <p>
                                Solicitudes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_request')
                            {{--<li class="nav-item">
                                <a href="{{ route('output.request.index') }}" class="nav-link @yield('activeListOutputRequest')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar solicitudes</p>
                                </a>
                            </li>--}}
                            <li class="nav-item">
                                <a href="{{ route('output.request.indexV2') }}" class="nav-link @yield('activeListOutputRequest')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar solicitudes</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_request')
                            <li class="nav-item">
                                <a href="{{ route('output.request.create') }}" class="nav-link @yield('activeCreateOutputRequest')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear solicitudes</p>
                                </a>
                            </li>
                            @endcan
                            @can('report_output')
                                <li class="nav-item">
                                    <a href="{{ route('report.materials.outputs') }}" class="nav-link @yield('activeReportMaterialOutput')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Materiales en salidas</p>
                                    </a>
                                </li>
                            @endcan
                            @can('report_output')
                                <li class="nav-item">
                                    <a href="{{ route('report.outputs.by.quote') }}" class="nav-link @yield('activeReportOutputByQuote')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reporte Salidas Cotización</p>
                                    </a>
                                </li>
                            @endcan
                            @can('report_request')
                                <li class="nav-item">
                                    <a href="{{ route('output.simple.reportByArea') }}" class="nav-link @yield('activeReportMaterialAreaOutputSimple')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reporte de Salida por área</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    @can('list_output')
                    <li class="nav-header">SALIDAS</li>
                    <li class="nav-item has-treeview @yield('openOutputs')">
                        <a href="#" class="nav-link @yield('activeOutputs')">
                            <i class="nav-icon fas fa-share"></i>
                            <p>
                                Salidas
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('output.confirm') }}" class="nav-link @yield('activeListOutput')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar salidas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('output.index.confirmed') }}" class="nav-link @yield('activeListOutputConfirmed')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Salidas confirmadas</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcan

                    @can('enable_followMaterials')
                        <li class="nav-header">ALERTA MATERIALES</li>
                        <li class="nav-item has-treeview @yield('openFollow')">
                            <a href="#" class="nav-link @yield('activeFollow')">
                                <i class="nav-icon fas fa-exclamation-triangle"></i>
                                <p>
                                    Seguimiento materiales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_followMaterials')
                                <li class="nav-item">
                                    <a href="{{ route('follow.index') }}" class="nav-link @yield('activeListFollow')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar materiales</p>
                                    </a>
                                </li>
                                @endcan
                                @can('stock_followMaterials')
                                    <li class="nav-item">
                                        <a href="{{ route('stock.index') }}" class="nav-link @yield('activeStockMaterials')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Stock materiales</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('list_requestPurchaseOperator')
                        <li class="nav-header">SOLICITUDES DE COMPRA</li>
                        <li class="nav-item has-treeview @yield('openRequestPurchase')">
                            <a href="#" class="nav-link @yield('activeRequestPurchase')">
                                <i class="nav-icon fas fa-paste"></i>
                                <p>
                                    Solicitudes de compra
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_requestPurchaseOperator')
                                    <li class="nav-item">
                                        <a href="{{ route('follow.index') }}" class="nav-link @yield('activeListRequestPurchase')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar solicitudes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('delete_requestPurchaseOperator')
                                    <li class="nav-item">
                                        <a href="{{ route('follow.index') }}" class="nav-link @yield('activeListRequestPurchase')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Solicitudes anuladas</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan


                    @canany(['list_orderPurchaseExpress', 'list_orderPurchaseNormal', 'list_orderPurchaseFinance'])
                    <li class="nav-header">ORDENES DE COMPRA</li>
                    @endcanany
                    @canany(['list_orderPurchaseNormal','list_orderPurchaseExpress'])
                        <li class="nav-item has-treeview @yield('openOrderPurchaseGeneral')">
                            <a href="#" class="nav-link @yield('activeOrderPurchaseGeneral')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Órdenes de compra
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                {{--@canany('list_orderPurchaseNormal','list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.general.indexV2')}}" class="nav-link @yield('activeListOrderPurchaseGeneral')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar ordenes general</p>
                                        </a>
                                    </li>
                                @endcanany--}}
                                {{--@can('list_orderPurchaseExpress')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.express.index')}}" class="nav-link @yield('activeListOrderPurchaseExpress')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar express</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.express.indexV2')}}" class="nav-link @yield('activeListOrderPurchaseExpress')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar express</p>
                                        </a>
                                    </li>
                                @endcan--}}
                                @can('list_orderPurchaseNormal')
                                    {{--<li class="nav-item">
                                        <a href="{{route('order.purchase.normal.index')}}" class="nav-link @yield('activeListOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar normal</p>
                                        </a>
                                    </li>--}}
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.normal.indexV2')}}" class="nav-link @yield('activeListOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar órdenes</p>
                                        </a>
                                    </li>
                                @endcan
                                {{--@can('list_orderPurchaseFinance')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.finance.index')}}" class="nav-link @yield('activeListOrderPurchaseFinance')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar Ord. finanzas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_orderPurchaseExpress')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.express.create') }}" class="nav-link @yield('activeCreateOrderPurchaseExpress')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear Express</p>
                                        </a>
                                    </li>
                                @endcan--}}
                                @can('create_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.normal.create') }}" class="nav-link @yield('activeCreateOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear orden</p>
                                        </a>
                                    </li>
                                @endcan
                                {{--@can('create_orderPurchaseFinance')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.finance.create') }}" class="nav-link @yield('activeCreateOrderPurchaseFinance')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear Ord. Finanza</p>
                                        </a>
                                    </li>
                                @endcan
                                @canany('list_orderPurchaseNormal','list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.list.regularize') }}" class="nav-link @yield('activeListOrderPurchaseRegularize')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar regularizadas</p>
                                        </a>
                                    </li>
                                @endcanany--}}
                                @canany(['destroy_orderPurchaseNormal','destroy_orderPurchaseNormal'])
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.delete')}}" class="nav-link @yield('activeListOrderPurchaseDelete')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar eliminadas</p>
                                        </a>
                                    </li>
                                @endcanany
                                {{--@canany('list_orderPurchaseNormal','list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.list.lost')}}" class="nav-link @yield('activeListOrderPurchaseLost')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ordenes perdidas</p>
                                        </a>
                                    </li>
                                @endcanany--}}

                                {{--@canany('list_orderPurchaseFinance')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.finance.list.regularize') }}" class="nav-link @yield('activeListOrderPurchaseFinanceRegularize')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ord. Finanzas Regularizada</p>
                                        </a>
                                    </li>
                                @endcanany
                                @canany('destroy_orderPurchaseFinance')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.finance.delete')}}" class="nav-link @yield('activeListOrderPurchaseFinanceDelete')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ord. Finanzas Eliminadas</p>
                                        </a>
                                    </li>
                                @endcanany
                                @canany('list_orderPurchaseFinance')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.finance.list.lost')}}" class="nav-link @yield('activeListOrderPurchaseFinanceLost')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ord. Finanzas perdidas</p>
                                        </a>
                                    </li>
                                @endcanany--}}

                            </ul>
                        </li>
                    @endcanany
                    @can('report_orderPurchaseExpress')
                    <li class="nav-item has-treeview @yield('openOrderPurchaseReports')">
                        <a href="#" class="nav-link @yield('activeOrderPurchaseReports')">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>
                                Reportes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('report_orderPurchaseExpress')
                                <li class="nav-item">
                                    <a href="{{route('report.orders.by.materials')}}" class="nav-link @yield('activeReportOrderPurchaseByMaterial')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Órdenes por Material</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan
                    {{--@can('list_orderPurchaseNormal')
                        <li class="nav-item has-treeview @yield('openOrderPurchaseNormal')">
                            <a href="#" class="nav-link @yield('activeOrderPurchaseNormal')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Ordenes Normales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.normal.index')}}" class="nav-link @yield('activeListOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar ordenes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.normal.create') }}" class="nav-link @yield('activeCreateOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear orden</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan--}}
                    {{--@can('enable_timeline')
                    <li class="nav-header">CONTROL DE HORAS</li>
                    @endcan--}}

                    {{--<li class="nav-item has-treeview @yield('openTimelines')">
                        @can('enable_timeline')
                        <a href="#" class="nav-link @yield('activeTimelines')">
                            <i class="nav-icon far fa-calendar-alt"></i>
                            <p>
                                Cronogramas
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('index_timeline')
                            <li class="nav-item">
                                <a href="{{ route('index.timelines') }}" class="nav-link @yield('activeShowTimelines')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ver cronogramas</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>--}}
                    @can('enable_worker')
                    <li class="nav-header">RECURSOS HUMANOS</li>
                    @endcan
                    <li class="nav-item has-treeview @yield('openConfigRH')">
                        @can('enableConfig_worker')
                        <a href="#" class="nav-link @yield('activeConfigRH')">
                            <i class="fas fa-users-cog nav-icon"></i>
                            <p>
                                Configuraciones
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('holiday_worker')
                                <li class="nav-item has-treeview @yield('openHoliday')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Feriados
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">

                                        <li class="nav-item">
                                            <a href="{{ route('holiday.index') }}" class="nav-link @yield('activeListHoliday')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar Feriados</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('holiday.create') }}" class="nav-link @yield('activeCreateHoliday')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear Feriado</p>
                                            </a>
                                        </li>

                                    </ul>
                                </li>
                            @endcan
                            @can('contract_worker')
                            <li class="nav-item has-treeview @yield('openContract')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Contratos
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('contract.index') }}" class="nav-link @yield('activeListContract')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar contratos</p>
                                        </a>
                                    </li>

                                    {{--<li class="nav-item">
                                        <a href="{{ route('contract.create') }}" class="nav-link @yield('activeCreateContract')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear contrato</p>
                                        </a>
                                    </li>--}}

                                    {{--<li class="nav-item">
                                        <a href="{{ route('contract.deleted') }}" class="nav-link @yield('activeListContractDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Contratos eliminados</p>
                                        </a>
                                    </li>--}}

                                </ul>
                            </li>
                            @endcan
                            @can('relationship_worker')
                                <li class="nav-item has-treeview @yield('openRelationships')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Parentescos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">

                                        <li class="nav-item">
                                            <a href="{{ route('relationship.index') }}" class="nav-link @yield('activeListRelationships')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar Parentesco</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('relationship.create') }}" class="nav-link @yield('activeCreateRelationships')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear Parentesco</p>
                                            </a>
                                        </li>

                                    </ul>
                                </li>
                            @endcan
                            @can('statusCivil_worker')
                            <li class="nav-item has-treeview @yield('openCivilStatus')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Estado Civil
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('civilStatuses.index') }}" class="nav-link @yield('activeListCivilStatus')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar Estados Civil</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('civilStatuses.create') }}" class="nav-link @yield('activeCreateCivilStatus')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear Estados Civil</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('civilStatuses.deleted') }}" class="nav-link @yield('activeListCivilStatusDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Estados Civil eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                            @can('function_worker')
                            <li class="nav-item has-treeview @yield('openWorkFunction')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Cargos / Funciones
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('workFunctions.index') }}" class="nav-link @yield('activeListWorkFunction')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar cargos</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('workFunctions.create') }}" class="nav-link @yield('activeCreateWorkFunction')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear cargo</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('workFunctions.deleted') }}" class="nav-link @yield('activeListWorkFunctionDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Cargos eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                            @can('systemPension_worker')
                            <li class="nav-item has-treeview @yield('openPensionSystem')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Sistemas de Pensión
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('pensionSystems.index') }}" class="nav-link @yield('activeListPensionSystem')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar sistemas</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('pensionSystems.create') }}" class="nav-link @yield('activeCreatePensionSystem')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear sistema</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('pensionSystems.deleted') }}" class="nav-link @yield('activeListPensionSystemDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Sistemas eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                            @can('list_areaWorker')
                                <li class="nav-item has-treeview @yield('openAreaWorker')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Áreas Empresa
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_areaWorker')
                                            <li class="nav-item">
                                                <a href="{{ route('areaWorker.index') }}" class="nav-link @yield('activeListAreaWorker')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar Áreas</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_percentageWorker')
                                            <li class="nav-item">
                                                <a href="{{ route('areaWorker.create') }}" class="nav-link @yield('activeCreateAreaWorker')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear Áreas</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                            @can('enable_bill')
                                <li class="nav-item has-treeview @yield('openBill')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Tipos de Gastos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_bill')
                                            <li class="nav-item">
                                                <a href="{{ route('bill.index') }}" class="nav-link @yield('activeListBill')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar Tipos</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_bill')
                                            <li class="nav-item">
                                                <a href="{{ route('bill.create') }}" class="nav-link @yield('activeCreateBill')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear Tipos</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                            @can('list_percentageWorker')
                                <li class="nav-item has-treeview @yield('openPercentageWorker')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Porcentajes
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_percentageWorker')
                                        <li class="nav-item">
                                            <a href="{{ route('percentageWorker.index') }}" class="nav-link @yield('activeListPercentageWorker')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar porcentajes</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_percentageWorker')
                                        <li class="nav-item">
                                            <a href="{{ route('percentageWorker.create') }}" class="nav-link @yield('activeCreatePercentageWorker')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear Porcentaje</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                        </ul>
                    </li>

                    <li class="nav-item has-treeview @yield('openWorker')">
                        @can('enable_worker')
                        <a href="#" class="nav-link @yield('activeWorker')">
                            <i class="fas fa-user-tie nav-icon"></i>
                            <p>
                                Colaboradores
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('list_worker')
                            <li class="nav-item">
                                <a href="{{route('worker.index')}}" class="nav-link @yield('activeListWorker')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar colaboradores</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_worker')
                            <li class="nav-item">
                                <a href="{{route('worker.create')}}" class="nav-link @yield('activeCreateWorker')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear colaboradores</p>
                                </a>
                            </li>
                            @endcan
                            @can('restore_worker')
                            <li class="nav-item">
                                <a href="{{route('worker.enable')}}" class="nav-link @yield('activeEnableWorker')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Habilitar colaboradores</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>

                    <li class="nav-item has-treeview @yield('openAttendance')">
                        @can('enable_assistance')
                        <a href="#" class="nav-link @yield('activeAttendance')">
                            <i class="far fa-calendar-check nav-icon"></i>
                            <p>
                                Asistencia
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('workingDay_assistance')
                            <li class="nav-item">
                                <a href="{{ route('workingDay.create') }}" class="nav-link @yield('activeListWorkingDay')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Jornadas Trabajo</p>
                                </a>
                            </li>
                            @endcan
                            @can('regime_assistance')
                            <li class="nav-item">
                                <a href="{{ route('regime.create') }}" class="nav-link @yield('activeListRegime')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Régimen de Trabajo</p>
                                </a>
                            </li>
                            @endcan
                            @canany(['register_assistance', 'report_assistance'])
                            <li class="nav-item">
                                <a href="{{ route('assistance.index') }}" class="nav-link @yield('activeListAttendance')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar asistencia</p>
                                </a>
                            </li>
                            @endcanany
                            @can('report_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show') }}" class="nav-link @yield('activeReportAttendance')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reporte asistencia</p>
                                    </a>
                                </li>
                            @endcan
                            @can('hourDiary_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show.hour.diary') }}" class="nav-link @yield('activeReportHoursDiary')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Horas Diarias</p>
                                    </a>
                                </li>
                            @endcan
                            @can('totalHours_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show.total.hours') }}" class="nav-link @yield('activeReportTotalHours')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Total Horas</p>
                                    </a>
                                </li>
                            @endcan
                            @can('totalPays_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show.total.pays') }}" class="nav-link @yield('activeReportTotalPays')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Total Neto</p>
                                    </a>
                                </li>
                            @endcan
                            @can('totalPays_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show.total.pays.accounts') }}" class="nav-link @yield('activeReportTotalPaysAccounts')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Total Neto Cuentas</p>
                                    </a>
                                </li>
                            @endcan
                            @can('totalBruto_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show.total.bruto') }}" class="nav-link @yield('activeReportTotalBruto')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Total Bruto</p>
                                    </a>
                                </li>
                            @endcan
                            @can('enable_permitHour')
                            <li class="nav-item has-treeview @yield('openPermitHour')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon "></i>
                                    <p>
                                        Permisos por Horas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_permitHour')
                                    <li class="nav-item">
                                        <a href="{{ route('permit_hour.index') }}" class="nav-link @yield('activeListPermitHour')">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>Listar permisos</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_permitHour')
                                    <li class="nav-item">
                                        <a href="{{ route('permit_hour.create') }}" class="nav-link @yield('activeCreatePermitHour')">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>Crear permiso</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @can('enable_hourSpecial')
                    <li class="nav-item has-treeview @yield('openHourSpecial')">
                        <a href="#" class="nav-link @yield('activeHourSpecial')">
                            <i class="fas fa-business-time nav-icon"></i>
                            <p>
                                Horas Especiales
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            @can('enable_medicalRest')
                                <li class="nav-item has-treeview @yield('openMedicalRest')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Descansos Médicos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_medicalRest')
                                            <li class="nav-item">
                                                <a href="{{ route('medicalRest.index') }}" class="nav-link @yield('activeListMedicalRest')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar descansos</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_medicalRest')
                                            <li class="nav-item">
                                                <a href="{{ route('medicalRest.create') }}" class="nav-link @yield('activeCreateMedicalRest')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear descanso</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                            @can('enable_vacation')
                                <li class="nav-item has-treeview @yield('openVacation')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Vacaciones
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_vacation')
                                            <li class="nav-item">
                                                <a href="{{ route('vacation.index') }}" class="nav-link @yield('activeListVacation')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar vacaciones</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_vacation')
                                            <li class="nav-item">
                                                <a href="{{ route('vacation.create') }}" class="nav-link @yield('activeCreateVacation')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Registrar vacaciones</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                            @can('enable_license')
                                <li class="nav-item has-treeview @yield('openLicenses')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Licencias
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_license')
                                            <li class="nav-item">
                                                <a href="{{ route('license.index') }}" class="nav-link @yield('activeListLicense')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar licencias</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_license')
                                            <li class="nav-item">
                                                <a href="{{ route('license.create') }}" class="nav-link @yield('activeCreateLicense')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear licencia</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                            @can('enable_unpaidLicense')
                                    <li class="nav-item has-treeview @yield('openUnpaidLicenses')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Licencias sin gozo
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_unpaidLicense')
                                                <li class="nav-item">
                                                    <a href="{{ route('unpaidLicense.index') }}" class="nav-link @yield('activeListUnpaidLicense')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar licencias sin gozo</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('create_unpaidLicense')
                                                <li class="nav-item">
                                                    <a href="{{ route('unpaidLicense.create') }}" class="nav-link @yield('activeCreateUnpaidLicense')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Crear licencia sin gozo</p>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                            @endcan

                            @can('enable_permit')
                                <li class="nav-item has-treeview @yield('openPermit')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Permisos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_permit')
                                            <li class="nav-item">
                                                <a href="{{ route('permit.index') }}" class="nav-link @yield('activeListPermit')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar permisos</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_permit')
                                            <li class="nav-item">
                                                <a href="{{ route('permit.create') }}" class="nav-link @yield('activeCreatePermit')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear permiso</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('enable_suspension')
                                <li class="nav-item has-treeview @yield('openSuspension')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Suspensiones
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_suspension')
                                            <li class="nav-item">
                                                <a href="{{ route('reasonSuspension.index') }}" class="nav-link @yield('activeListReasonSuspension')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Razones de Suspensión</p>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('suspension.index') }}" class="nav-link @yield('activeListSuspension')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar suspensiones</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_suspension')
                                            <li class="nav-item">
                                                <a href="{{ route('suspension.create') }}" class="nav-link @yield('activeCreateSuspension')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear suspension</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('enable_discountContribution')
                        <li class="nav-item has-treeview @yield('openDiscountContribution')">
                            <a href="#" class="nav-link @yield('activeDiscountContribution')">
                                <i class="fas fa-hand-holding-usd nav-icon"></i>
                                <p>
                                    Desc./Contrib.
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">
                                @can('enable_discount')
                                    <li class="nav-item has-treeview @yield('openDiscounts')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Descuentos
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_discount')
                                                <li class="nav-item">
                                                    <a href="{{ route('discount.index') }}" class="nav-link @yield('activeListDiscount')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar descuentos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('create_discount')
                                                <li class="nav-item">
                                                    <a href="{{ route('discount.create') }}" class="nav-link @yield('activeCreateDiscount')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Registrar descuento</p>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                                @can('enable_refund')
                                    <li class="nav-item has-treeview @yield('openRefunds')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Reembolsos
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_refund')
                                                <li class="nav-item">
                                                    <a href="{{ route('refund.index') }}" class="nav-link @yield('activeListRefund')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar reembolsos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('create_refund')
                                                <li class="nav-item">
                                                    <a href="{{ route('refund.create') }}" class="nav-link @yield('activeCreateRefund')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Registrar reembolso</p>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                                @can('enable_expense')
                                    <li class="nav-item has-treeview @yield('openExpense')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Rendición de gastos
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_expense')
                                                <li class="nav-item">
                                                    <a href="{{ route('expense.index') }}" class="nav-link @yield('activeListExpense')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listado de gastos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('create_expense')
                                                <li class="nav-item">
                                                    <a href="{{ route('expense.create') }}" class="nav-link @yield('activeCreateExpense')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Registrar gasto</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('report_expense')
                                                <li class="nav-item">
                                                    <a href="{{ route('expense.report') }}" class="nav-link @yield('activeReportExpense')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Reporte de gastos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                                @can('enable_loan')
                                    <li class="nav-item has-treeview @yield('openLoans')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Préstamos
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_loan')
                                                <li class="nav-item">
                                                    <a href="{{ route('loan.index') }}" class="nav-link @yield('activeListLoan')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar préstamos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('create_loan')
                                                <li class="nav-item">
                                                    <a href="{{ route('loan.create') }}" class="nav-link @yield('activeCreateLoan')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Registrar préstamos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                                @can('enable_gratification')
                                    <li class="nav-item has-treeview @yield('openGratifications')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Gratificaciones
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_gratification')
                                                <li class="nav-item">
                                                    <a href="{{ route('gratification.index') }}" class="nav-link @yield('activeListGratification')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar gratificación</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            {{--@can('create_gratification')
                                                <li class="nav-item">
                                                    <a href="{{ route('gratification.create') }}" class="nav-link @yield('activeCreateGratification')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Crear gratificación</p>
                                                    </a>
                                                </li>
                                            @endcan--}}
                                        </ul>
                                    </li>
                                @endcan

                                @can('enable_fifthCategory')
                                    <li class="nav-item has-treeview @yield('openFifthCategory')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Quinta Categoría
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_fifthCategory')
                                                <li class="nav-item">
                                                    <a href="{{ route('fifthCategory.index') }}" class="nav-link @yield('activeFifthCategory')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar Usuarios</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            {{--@can('create_gratification')
                                                <li class="nav-item">
                                                    <a href="{{ route('gratification.create') }}" class="nav-link @yield('activeCreateGratification')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Crear gratificación</p>
                                                    </a>
                                                </li>
                                            @endcan--}}
                                        </ul>
                                    </li>
                                @endcan

                                @can('enable_alimony')
                                    <li class="nav-item has-treeview @yield('openAlimony')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Pensión Alimentos
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_alimony')
                                                <li class="nav-item">
                                                    <a href="{{ route('alimony.index') }}" class="nav-link @yield('activeAlimony')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar Usuarios</p>
                                                    </a>
                                                </li>
                                            @endcan

                                        </ul>
                                    </li>
                                @endcan

                                @can('enable_bonusRisk')
                                    <li class="nav-item has-treeview @yield('openSpecialBonus')">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle nav-icon text-success"></i>
                                            <p>
                                                Bonos especiales
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @can('list_bonusRisk')
                                                <li class="nav-item">
                                                    <a href="{{ route('bonusRisk.index') }}" class="nav-link @yield('activeListBonus')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Listar bonos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('create_bonusRisk')
                                                <li class="nav-item">
                                                    <a href="{{ route('bonusRisk.create') }}" class="nav-link @yield('activeCreateBonus')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Registrar bonos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('report_bonusRisk')
                                                <li class="nav-item">
                                                    <a href="{{ route('bonusRisk.report') }}" class="nav-link @yield('activeReportBonus')">
                                                        <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                        <p>Reporte de bonos</p>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('enable_paySlip')
                        <li class="nav-item has-treeview @yield('openPaySlips')">
                            @can('enable_paySlip')
                            <a href="#" class="nav-link @yield('activePaySlips')">
                                <i class="far fa-clipboard nav-icon"></i>
                                <p>
                                    Boletas de Pago
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            @endcan
                            <ul class="nav nav-treeview">
                                @can('list_paySlip')
                                    <li class="nav-item">
                                        <a href="{{route('paySlip.index')}}" class="nav-link @yield('activeListPaySlip')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar boletas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_paySlip')
                                    <li class="nav-item">
                                        <a href="{{route('paySlip.create')}}" class="nav-link @yield('activeCreatePaySlip')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear boletas</p>
                                        </a>
                                    </li>
                                @endcan

                            </ul>
                        </li>
                    @endcan

                    @can('enable_personalPayments')
                        <li class="nav-item has-treeview @yield('openPersonalPayments')">
                            <a href="#" class="nav-link @yield('activePersonalPayments')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Pagos al Personal
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_personalPayments')
                                    <li class="nav-item">
                                        <a href="{{ route('personal.payments.index') }}" class="nav-link @yield('activeListPersonalPayments')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar pagos</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @canany(['enable_orderService'])
                        <li class="nav-header">ORDENES DE SERVICIO</li>
                    @endcanany
                    @can('watch_orderService')
                        <li class="nav-item has-treeview @yield('openOrderService')">
                            <a href="#" class="nav-link @yield('activeOrderService')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Ordenes de servicio
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_orderService')
                                    <li class="nav-item">
                                        <a href="{{ route('order.service.index') }}" class="nav-link @yield('activeListOrderService')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar ordenes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_orderService')
                                    <li class="nav-item">
                                        <a href="{{ route('order.service.create') }}" class="nav-link @yield('activeCreateOrderService')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear orden</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_orderService')
                                    <li class="nav-item">
                                        <a href="{{ route('order.service.list.regularize') }}" class="nav-link @yield('activeListOrderServiceRegularize')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar regularizadas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_orderService')
                                    <li class="nav-item">
                                        <a href="{{ route('order.service.list.deleted') }}" class="nav-link @yield('activeListOrderServiceDeleted')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar Anuladas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_orderService')
                                    <li class="nav-item">
                                        <a href="{{ route('order.service.list.lost') }}" class="nav-link @yield('activeListOrderServiceLost')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ordenes Perdidas</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @canany('enable_sales')
                        <li class="nav-header">VENTAS</li>
                    @endcanany

                    @can('enable_defaultEquipment')
                        <li class="nav-item has-treeview @yield('openDefaultEquipment')">
                            <a href="#" class="nav-link @yield('activeDefaultEquipment')">
                                <i class="nav-icon fas fa-th-list"></i>
                                <p>
                                     Catálogo de equipos
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('listCategory_defaultEquipment')
                                    <li class="nav-item">
                                        <a href="{{route('categoryEquipment.index')}}" class="nav-link @yield('activeCategoryEquipment')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ver catálogo</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('eliminatedCategory_defaultEquipment')
                                <li class="nav-item">
                                    <a href="{{route('categoryEquipment.eliminated')}}" class="nav-link @yield('activeCategoryEquipmentDelete')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Categorias Eliminadas</p>
                                    </a>
                                </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan
                    @can('enable_defaultEquipment')
                        <li class="nav-item has-treeview @yield('openProforma')">
                            <a href="#" class="nav-link @yield('activeProforma')">
                                <i class="nav-icon fas fa-th-list"></i>
                                <p>
                                    Pre - Cotizaciones
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('listCategory_defaultEquipment')
                                    <li class="nav-item">
                                        <a href="{{route('proforma.index')}}" class="nav-link @yield('activeListProforma')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('eliminatedCategory_defaultEquipment')
                                    <li class="nav-item">
                                        <a href="{{ route('proforma.create') }}" class="nav-link @yield('activeCreateProforma')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear Pre-Cotización</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @canany('list_invoice')
                        <li class="nav-header">FINANZAS</li>
                    @endcanany

                    {{--@can('list_invoice')
                        <li class="nav-item has-treeview @yield('openMaterialsActivos')">
                            <a href="#" class="nav-link @yield('activeMaterialsActivos')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Activos Fijos
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_invoice')
                                    <li class="nav-item">
                                        <a href="{{route('invoice.materials.fijos')}}" class="nav-link @yield('activeListMaterialsActivos')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar Activos fijos</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan--}}

                    @can('list_categoryInvoice')
                        <li class="nav-item has-treeview @yield('openCategoryInvoice')">
                            <a href="#" class="nav-link @yield('activeCategoryInvoice')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Categorías
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_categoryInvoice')
                                    <li class="nav-item">
                                        <a href="{{route('categoryInvoice.index')}}" class="nav-link @yield('activeListCategoryInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar categorías</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_categoryInvoice')
                                    <li class="nav-item">
                                        <a href="{{ route('categoryInvoice.create') }}" class="nav-link @yield('activeCreateCategoryInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear categoría</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('list_invoice')
                        <li class="nav-item has-treeview @yield('openInvoice')">
                            <a href="#" class="nav-link @yield('activeInvoice')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Compras
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_invoice')
                                    <li class="nav-item">
                                        <a href="{{route('invoice.index')}}" class="nav-link @yield('activeListInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Facturas finanzas</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{route('invoice.index.annulled')}}" class="nav-link @yield('activeListInvoiceAnnulled')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Facturas anuladas</p>
                                        </a>
                                    </li>
                                    {{--<li class="nav-item">
                                        <a href="{{route('report.invoice.finance')}}" class="nav-link @yield('activeReportInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Facturas finanzas</p>
                                        </a>
                                    </li>--}}
                                    {{--<li class="nav-item">
                                        <a href="{{route('report.invoice.finance.sin.orden')}}" class="nav-link @yield('activeReportInvoiceSinOrden')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Facturas finanzas Sin Orden</p>
                                        </a>
                                    </li>--}}
                                @endcan
                                @can('create_invoice')
                                    <li class="nav-item">
                                        <a href="{{ route('invoice.create') }}" class="nav-link @yield('activeCreateInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear compra/Servicios</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan
                    @can('show_service')
                        <li class="nav-item has-treeview @yield('openService')">
                            <a href="#" class="nav-link @yield('activeService')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Servicios
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                {{--@can('list_service')
                                    <li class="nav-item">
                                        <a href="{{route('service.index')}}" class="nav-link @yield('activeListService')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar servicios</p>
                                        </a>
                                    </li>
                                @endcan--}}
                                @can('list_orderService')
                                    <li class="nav-item">
                                        <a href="{{ route('order.service.index') }}" class="nav-link @yield('activeListOrderService')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar órdenes</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('enable_financeWorks')
                        <li class="nav-item has-treeview @yield('openFinanceWorks')">
                            <a href="#" class="nav-link @yield('activeFinanceWorks')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Cuadros Financieros
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_financeWorks')
                                    <li class="nav-item">
                                        <a href="{{ route('finance.works.index') }}" class="nav-link @yield('activeListFinanceWorks')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ingresos Clientes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('enable_expenseSupplier')
                                    <li class="nav-item">
                                        <a href="{{ route('expenses.supplier.index') }}" class="nav-link @yield('activeListExpensesSupplier')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Egresos Proveedores</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan
                    @can('enable_credit')
                    <li class="nav-header">CRÉDITOS</li>
                    <li class="nav-item has-treeview @yield('openCredit')">
                        <a href="#" class="nav-link @yield('activeCreditSupplier')">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>
                                CRÉDITOS PROVEEDOR
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @can('control_credit')
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{route('index.invoices.pending')}}" class="nav-link @yield('activeListCreditSupplier')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Facturas pendientes</p>
                                </a>
                            </li>
                        </ul>
                        @endcan
                    </li>
                    @endcan

                    {{--@canany('list_report')
                        <li class="nav-header">REPORTES</li>
                    @endcanany
                    @can('list_report')
                        <li class="nav-item has-treeview @yield('openReport')">
                            <a href="#" class="nav-link @yield('activeReport')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    REPORTE COTIZACIÓN
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('quote_report')
                                    <li class="nav-item">
                                        <a href="{{route('report.quote.index')}}" class="nav-link @yield('activeReportQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Cotizaciones</p>
                                        </a>
                                    </li>
                                @endcan

                            </ul>
                        </li>
                    @endcan--}}
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        @yield('page-header')
                        {{--<h1 class="m-0 text-dark">Starter Page</h1>--}}

                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        @yield('page-breadcrumb')
                        {{--<ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Starter Page</li>
                        </ol>--}}

                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    @yield('page-title')
                    {{--<h5 class="card-title">Card header</h5>--}}
                </div>
                <div class="card-body" id="content-body">
                    @yield('content')
                    {{--<h5 class="card-title">Card title</h5>--}}
                </div>
                {{--<div class="card-footer text-muted">
                    <a href="#" class="btn btn-primary">Card link</a>
                    <a href="#" class="card-link">Another link</a>
                </div>--}}
            </div>
            @yield('content-report')
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <!-- Default to the left -->
        <strong>Copyright &copy; <script>document.write(new Date().getFullYear());</script> <a href="https://www.edesce.com/">EDESCE</a>.</strong> Todos los derechos reservados.
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('admin/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin/plugins/pace-progress/pace.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ asset('admin/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('admin/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
<script src="{{ asset('admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
@yield('plugins')

<!-- AdminLTE App -->
<script src="{{ asset('admin/dist/js/adminlte.min.js') }}"></script>
<script src="{{ asset('/js/layout/admin2.js') }}"></script>

@yield('scripts')

</body>
</html>
