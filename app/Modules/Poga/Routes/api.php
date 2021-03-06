<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the 'api' middleware group. Enjoy building your API!
|
 */

// Auth
Route::get('usuario-invitado/{codigo_validacion}', 'Auth\UsuarioInvitadoController');
Route::post('token-para-pagos', 'Cuenta\TokenParaPagosController');
Route::post('reenviar-invitacion-registro', 'Auth\ReenviarInvitacionRegistroController');
Route::post('register', 'Auth\RegisterController@register');
Route::post('seleccionar-rol', Auth\SeleccionarRolController::class);
Route::put('registro-invitado/{codigo_validacion}', 'Auth\GuestRegisterController@register');
Route::apiResource('ciudades-cobertura', Auth\CiudadCoberturaController::class, ['only' => ['index']]);
Route::apiResource('paises-cobertura', Auth\PaisCoberturaController::class, ['only' => ['index']]);
Route::apiResource('roles', Auth\RolController::class, ['only' => ['index']]);

// Cuenta
Route::post('cuenta', 'Cuenta\ActualizarCuentaController');
Route::post('completa-tu-registro', 'Cuenta\CompletarRegistroController');
Route::put('actualiza-tu-celular', 'Cuenta\ActualizarTelefonoCelularController');

// Base97
Route::post('pagos/notificacion', Base97\NotificacionPagoController::class);
Route::apiResource('pagos', Base97\PagoController::class);

// Espacios
Route::apiResource('espacios', EspacioController::class);

// Eventos
Route::apiResource('reservas', Eventos\ReservaController::class);
Route::apiResource('visitas', Eventos\VisitaController::class);

// Finanzas
Route::post('finanzas/crearPago', Finanzas\CrearPagoController::class);
Route::post('finanzas/registrar-pago-manual', Finanzas\RegistrarPagoManualController::class);
Route::put('finanzas/actualizarEstadoPago', Finanzas\ActualizarEstadoPagareController::class);
Route::put('finanzas/cargarFondoReserva', Finanzas\CargarFondoReservaController::class);
Route::put('finanzas/confirmarPago', Finanzas\ConfirmarPagoController::class);
Route::put('finanzas/rechazarPago', Finanzas\RechazarPagoController::class);
Route::put('finanzas/transferirPago/{id}', Finanzas\TransferirPagareController::class);
Route::put('finanzas/distribuirExpensas', Finanzas\DistribuirExpensasController::class);
Route::put('finanzas/rentas/anular-pago/{id}', Finanzas\AnularPagareController::class);
Route::put('finanzas/activar-ley-emergencia/{id}', Finanzas\ActivarLeyEmergenciaController::class);
Route::put('finanzas/actualizarMontoPago/{id}', Finanzas\ActualizarMontoPagoController::class);
Route::get('finanzas/boletas-renta', Finanzas\BoletasRentaController::class);

Route::apiResource('bancos', Finanzas\BancoController::class, ['only' => ['index']]);
Route::apiResource('monedas', Finanzas\MonedaController::class, ['only' => ['index','show']]);
Route::apiResource('pagares', Finanzas\PagareController::class);

// Inmuebles
Route::put('inmuebles/desvincular', Inmuebles\DesvincularController::class);
Route::apiResource('estados-inmueble', Inmuebles\EstadoInmuebleController::class, ['only' => ['index']]);
Route::apiResource('formatos', Inmuebles\FormatoController::class, ['only' => ['index']]);
Route::apiResource('inmuebles', Inmuebles\InmuebleController::class);
Route::apiResource('medidas', Inmuebles\MedidaController::class, ['only' => ['index']]);
Route::apiResource('inmueble-personas', Inmuebles\PersonaController::class);
Route::apiResource('tipos-caracteristica', Inmuebles\TipoCaracteristicaController::class);
Route::apiResource('caracteristicas-tipo-inmueble', Inmuebles\CaracteristicaTipoInmuebleController::class);
Route::apiResource('tipos-inmueble', Inmuebles\TipoInmuebleController::class, ['only' => ['index']]);
Route::apiResource('unidades', Inmuebles\UnidadController::class);

// Mantenimientos
Route::post('mantenimientos/crearPago', Mantenimientos\CrearPagoController::class);
Route::put('mantenimientos/confirmarPago', Mantenimientos\ConfirmarPagoController::class);
Route::put('mantenimientos/rechazarPago', Mantenimientos\RechazarPagoController::class);
Route::apiResource('mantenimientos', Mantenimientos\MantenimientoController::class);

// Nominaciones
Route::put('nominaciones/{id}/aceptar', Nominaciones\AceptarController::class);
Route::put('nominaciones/{id}/rechazar', Nominaciones\RechazarController::class);
Route::apiResource('nominaciones', Nominaciones\NominacionController::class);

// Paises
Route::apiResource('paises', PaisController::class, ['only' => ['index']]);

// Personas
Route::apiResource('personas', PersonaController::class, ['only' => ['index','store']]);

// Proveedores
Route::apiResource('proveedores', ProveedorController::class);
Route::apiResource('proveedor-servicios', ProveedorServicioController::class);

// Rentas
Route::put('rentas/{id}/finalizarContrato', Rentas\FinalizarContratoController::class);
Route::put('rentas/{id}/renovarContrato', Rentas\RenovarContratoController::class);
Route::apiResource('rentas', Rentas\RentaController::class);

//
Route::get('reportes', ReporteController::class);

// Servicios
Route::apiResource('servicios', ServicioController::class);

// Solicitudes
Route::post('solicitudes/crearPago', Solicitudes\CrearPagoController::class);
Route::put('solicitudes/confirmarPago', Solicitudes\ConfirmarPagoController::class);
Route::put('solicitudes/rechazarPago', Solicitudes\RechazarPagoController::class);
Route::apiResource('solicitudes', Solicitudes\SolicitudController::class);
Route::apiResource('tipos-solicitud', Solicitudes\TipoSolicitudController::class);

// Usuarios
Route::apiResource('usuarios', Usuarios\UsuarioController::class, ['only' => ['show']]);
