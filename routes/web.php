<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\MetaController;
use \App\Http\Controllers\MaterialDetailSettingController;
use \App\Http\Controllers\MaterialPresentationController;
use \App\Http\Controllers\StoreWebController;
use \App\Http\Controllers\FreeSaleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome2');
    //return view('welcome');
});

//LANDING
Route::get('/nosotros', 'LandingController@about')->name('landing.about');
Route::get('/fabricacion', 'LandingController@manufacturing')->name('landing.manufacturing');
Route::get('/servicios', 'LandingController@service')->name('landing.service');
Route::get('/contacto', 'LandingController@contact')->name('landing.contact');
Route::post('/emailcontact', 'EmailController@sendEmailContact')->name('email.contact');



Auth::routes();

Route::middleware(['auth', 'check.user.enabled'])->group(function (){
    Route::prefix('dashboard')->group(function (){
        Route::get('/principal', 'HomeController@dashboard')->name('dashboard.principal');

        // TODO: Rutas módulo Accesos

        //USER
        Route::get('usuarios', 'UserController@index')->name('user.index')
            ->middleware('permission:list_user');
        Route::post('user/store', 'UserController@store')->name('user.store')
            ->middleware('permission:create_user');
        Route::post('user/update', 'UserController@update')->name('user.update')
            ->middleware('permission:update_user');
        Route::get('user/roles/{id}', 'UserController@getRoles')->name('user.roles')
            ->middleware('permission:update_user');
        Route::post('user/destroy', 'UserController@destroy')->name('user.destroy')
            ->middleware('permission:destroy_user');
        Route::get('/all/users', 'UserController@getUsers');
        Route::get('/user/roles/{id}', 'UserController@getRoles')->name('user.roles')
            ->middleware('permission:update_user');

        Route::get('usuarios/eliminados', 'UserController@indexEnable')->name('user.indexEnable')
            ->middleware('permission:list_user');
        Route::get('/all/users/delete', 'UserController@getUsersDelete');
        Route::post('user/disable', 'UserController@disable')->name('user.disable')
            ->middleware('permission:destroy_user');
        Route::post('user/enable', 'UserController@enable')->name('user.enable')
            ->middleware('permission:destroy_user');

        Route::get('users/to/workers', 'UserController@convertUsersToWorkers')
            ->middleware('permission:create_permission');

        //CUSTOMER
        Route::get('/all/customers', 'CustomerController@getCustomers')
            ->middleware('permission:list_customer');
        Route::get('clientes', 'CustomerController@index')
            ->name('customer.index')
            ->middleware('permission:list_customer');
        Route::get('crear/cliente', 'CustomerController@create')
            ->name('customer.create')
            ->middleware('permission:create_customer');
        Route::post('customer/store', 'CustomerController@store')
            ->name('customer.store')
            ->middleware('permission:create_customer');
        Route::get('/editar/cliente/{id}', 'CustomerController@edit')
            ->name('customer.edit')
            ->middleware('permission:update_customer');
        Route::post('customer/update', 'CustomerController@update')
            ->name('customer.update')
            ->middleware('permission:update_customer');
        Route::post('customer/destroy', 'CustomerController@destroy')
            ->name('customer.destroy')
            ->middleware('permission:destroy_customer');
        Route::get('clientes/restore', 'CustomerController@indexrestore')
            ->name('customer.indexrestore')
            ->middleware('permission:destroy_customer');
        Route::get('/all/customers/destroy', 'CustomerController@getCustomersDestroy')
            ->middleware('permission:destroy_customer');
        Route::post('customer/restore', 'CustomerController@restore')
            ->name('customer.restore')
            ->middleware('permission:destroy_customer');

        //SUPPLIER
        Route::get('/all/suppliers', 'SupplierController@getSuppliers')
            ->middleware('permission:list_supplier');
        Route::get('proveedores', 'SupplierController@index')->name('supplier.index')
            ->middleware('permission:list_supplier');
        Route::get('crear/proveedor', 'SupplierController@create')->name('supplier.create')
            ->middleware('permission:create_supplier');
        Route::post('supplier/store', 'SupplierController@store')->name('supplier.store')
            ->middleware('permission:create_supplier');
        Route::get('/editar/proveedor/{id}', 'SupplierController@edit')->name('supplier.edit')
            ->middleware('permission:update_supplier');
        Route::post('supplier/update', 'SupplierController@update')->name('supplier.update')
            ->middleware('permission:update_supplier');
        Route::post('supplier/destroy', 'SupplierController@destroy')->name('supplier.destroy')
            ->middleware('permission:destroy_supplier');
        Route::get('proveedores/restore', 'SupplierController@indexrestore')->name('supplier.indexrestore')
            ->middleware('permission:destroy_supplier');
        Route::get('/all/suppliers/destroy', 'SupplierController@getSuppliersDestroy')
            ->middleware('permission:destroy_supplier');
        Route::post('supplier/restore', 'SupplierController@restore')->name('supplier.restore')
            ->middleware('permission:destroy_supplier');

        //CONTACT NAME
        Route::get('/all/contacts', 'ContactNameController@getContacts')
            ->middleware('permission:list_contactName');
        Route::get('contactos', 'ContactNameController@index')
            ->name('contactName.index')
            ->middleware('permission:list_contactName');
        Route::get('crear/contacto', 'ContactNameController@create')
            ->name('contactName.create')
            ->middleware('permission:create_contactName');
        Route::post('contact/store', 'ContactNameController@store')
            ->name('contactName.store')
            ->middleware('permission:create_contactName');
        Route::get('/editar/contacto/{id}', 'ContactNameController@edit')
            ->name('contactName.edit')
            ->middleware('permission:update_contactName');
        Route::post('contact/update', 'ContactNameController@update')
            ->name('contactName.update')
            ->middleware('permission:update_contactName');
        Route::post('contact/destroy', 'ContactNameController@destroy')
            ->name('contactName.destroy')
            ->middleware('permission:destroy_contactName');
        Route::get('contactos/restore', 'ContactNameController@indexrestore')
            ->name('contactName.indexrestore')
            ->middleware('permission:destroy_contactName');
        Route::get('/all/contacts/destroy', 'ContactNameController@getContactsDestroy')
            ->middleware('permission:destroy_contactName');
        Route::post('contact/restore', 'ContactNameController@restore')
            ->name('contactName.restore')
            ->middleware('permission:destroy_contactName');

        //MATERIAL TYPE
        Route::get('/all/materialtypes', 'MaterialTypeController@getMaterialTypes')
            ->middleware('permission:list_materialType');
        Route::get('TiposMateriales', 'MaterialTypeController@index')
            ->name('materialtype.index')
            ->middleware('permission:list_materialType');
        Route::get('crear/tipomaterial', 'MaterialTypeController@create')
            ->name('materialtype.create')
            ->middleware('permission:create_materialType');
        Route::post('materialtype/store', 'MaterialTypeController@store')
            ->name('materialtype.store')
            ->middleware('permission:create_materialType');
        Route::get('/editar/tipomaterial/{id}', 'MaterialTypeController@edit')
            ->name('materialtype.edit')
            ->middleware('permission:update_materialType');
        Route::post('materialtype/update', 'MaterialTypeController@update')
            ->name('materialtype.update')
            ->middleware('permission:update_materialType');
        Route::post('materialtype/destroy', 'MaterialTypeController@destroy')
            ->name('materialtype.destroy')
            ->middleware('permission:destroy_materialType');
        Route::get('/get/types/{subcategory_id}', 'MaterialTypeController@getTypesBySubCategory')
            ->middleware('permission:destroy_materialType');
        Route::post('/materialtype/delete-multiple', 'MaterialTypeController@deleteMultiple');

        //SUB TYPE
        Route::get('/all/subtypes', 'SubtypeController@getSubTypes')
            ->middleware('permission:list_subType');
        Route::get('Subtipos', 'SubtypeController@index')
            ->name('subtype.index')
            ->middleware('permission:list_subType');
        Route::get('crear/subtipo', 'SubtypeController@create')
            ->name('subtype.create')
            ->middleware('permission:create_subType');
        Route::post('subtype/store', 'SubtypeController@store')
            ->name('subtype.store')
            ->middleware('permission:create_subType');
        Route::get('/editar/subtipo/{id}', 'SubtypeController@edit')
            ->name('subtype.edit')
            ->middleware('permission:update_subType');
        Route::post('subtype/update', 'SubtypeController@update')
            ->name('subtype.update')
            ->middleware('permission:update_subType');
        Route::post('subtype/destroy', 'SubtypeController@destroy')
            ->name('subtype.destroy')
            ->middleware('permission:destroy_subType');
        Route::get('/get/subtypes/{type_id}', 'SubtypeController@getSubTypesByType');
        Route::post('/subtype/delete-multiple', 'SubtypeController@deleteMultiple');


        //CATEGORY
        Route::get('/all/categories', 'CategoryController@getCategories')
            ->middleware('permission:list_category');
        Route::get('Categorias', 'CategoryController@index')
            ->name('category.index')
            ->middleware('permission:list_category');
        Route::get('crear/categoria', 'CategoryController@create')
            ->name('category.create')
            ->middleware('permission:create_category');
        Route::post('category/store', 'CategoryController@store')
            ->name('category.store')
            ->middleware('permission:create_category');
        Route::get('/editar/categoria/{id}', 'CategoryController@edit')
            ->name('category.edit')
            ->middleware('permission:update_category');
        Route::post('category/update', 'CategoryController@update')
            ->name('category.update')
            ->middleware('permission:update_category');
        Route::post('category/destroy', 'CategoryController@destroy')
            ->name('category.destroy')
            ->middleware('permission:destroy_category');
        Route::get('/get/subcategories/{category_id}', 'CategoryController@getSubcategoryByCategory');
        Route::post('/category/delete-multiple', 'CategoryController@deleteMultiple');


        //SUBCATEGORY
        Route::get('/all/subcategories', 'SubcategoryController@getSubcategories')
            ->middleware('permission:list_subcategory');
        Route::get('Subcategorias', 'SubcategoryController@index')
            ->name('subcategory.index')
            ->middleware('permission:list_subcategory');
        Route::get('crear/subcategoria', 'SubcategoryController@create')
            ->name('subcategory.create')
            ->middleware('permission:create_subcategory');
        Route::post('subcategory/store', 'SubcategoryController@store')
            ->name('subcategory.store')
            ->middleware('permission:create_subcategory');
        Route::post('subcategory/store/individual', 'SubcategoryController@storeIndividual')
            ->name('subcategory.store.individual')
            ->middleware('permission:create_subcategory');
        Route::get('/editar/subcategoria/{id}', 'SubcategoryController@edit')
            ->name('subcategory.edit')
            ->middleware('permission:update_subcategory');
        Route::post('subcategory/update', 'SubcategoryController@update')
            ->name('subcategory.update')
            ->middleware('permission:update_subcategory');
        Route::post('subcategory/destroy', 'SubcategoryController@destroy')
            ->name('subcategory.destroy')
            ->middleware('permission:destroy_subcategory');
        Route::post('/subcategory/delete-multiple', 'SubcategoryController@deleteMultiple');


        //CATEGORY INVOICES
        Route::get('/all/categories/invoices', 'CategoryInvoiceController@getCategories')
            ->middleware('permission:list_categoryInvoice');
        Route::get('/categorias/facturas', 'CategoryInvoiceController@index')
            ->name('categoryInvoice.index')
            ->middleware('permission:list_categoryInvoice');
        Route::get('crear/categoria/facturas', 'CategoryInvoiceController@create')
            ->name('categoryInvoice.create')
            ->middleware('permission:create_categoryInvoice');
        Route::post('category/invoice/store', 'CategoryInvoiceController@store')
            ->name('categoryInvoice.store')
            ->middleware('permission:create_categoryInvoice');
        Route::get('/editar/categoria/factura/{id}', 'CategoryInvoiceController@edit')
            ->name('categoryInvoice.edit')
            ->middleware('permission:update_categoryInvoice');
        Route::post('category/invoice/update', 'CategoryInvoiceController@update')
            ->name('categoryInvoice.update')
            ->middleware('permission:update_categoryInvoice');
        Route::post('category/invoice/destroy', 'CategoryInvoiceController@destroy')
            ->name('categoryInvoice.destroy')
            ->middleware('permission:destroy_categoryInvoice');

        //EXAMPLER
        Route::get('/all/examplers', 'ExamplerController@getExamplers')
            ->middleware('permission:list_exampler');
        Route::get('Modelos', 'ExamplerController@index')
            ->name('exampler.index')
            ->middleware('permission:list_exampler');
        Route::get('crear/modelo', 'ExamplerController@create')
            ->name('exampler.create')
            ->middleware('permission:create_exampler');
        Route::post('exampler/store', 'ExamplerController@store')
            ->name('exampler.store')
            ->middleware('permission:create_exampler');
        Route::get('/editar/modelo/{id}', 'ExamplerController@edit')
            ->name('exampler.edit')
            ->middleware('permission:update_exampler');
        Route::post('exampler/update', 'ExamplerController@update')
            ->name('exampler.update')
            ->middleware('permission:update_exampler');
        Route::post('exampler/destroy', 'ExamplerController@destroy')
            ->name('exampler.destroy')
            ->middleware('permission:destroy_exampler');
        Route::post('/exampler/delete-multiple', 'ExamplerController@deleteMultiple');


        //BRAND
        Route::get('/all/brands', 'BrandController@getBrands')
            ->middleware('permission:list_brand');
        Route::get('Marcas', 'BrandController@index')
            ->name('brand.index')
            ->middleware('permission:list_brand');
        Route::get('crear/marca', 'BrandController@create')
            ->name('brand.create')
            ->middleware('permission:create_brand');
        Route::post('brand/store', 'BrandController@store')
            ->name('brand.store')
            ->middleware('permission:create_brand');
        Route::get('/editar/marca/{id}', 'BrandController@edit')
            ->name('brand.edit')
            ->middleware('permission:update_brand');
        Route::post('brand/update', 'BrandController@update')
            ->name('brand.update')
            ->middleware('permission:update_brand');
        Route::post('brand/destroy', 'BrandController@destroy')
            ->name('brand.destroy')
            ->middleware('permission:destroy_brand');
        Route::get('/get/exampler/{brand_id}', 'BrandController@getJsonBrands');
        Route::post('/brand/delete-multiple', 'BrandController@deleteMultiple');

        //CEDULA
        Route::get('/all/warrants', 'WarrantController@getWarrants')
            ->middleware('permission:list_warrant');
        Route::get('Cédulas', 'WarrantController@index')
            ->name('warrant.index')
            ->middleware('permission:list_warrant');
        Route::get('crear/cedula', 'WarrantController@create')
            ->name('warrant.create')
            ->middleware('permission:create_warrant');
        Route::post('warrant/store', 'WarrantController@store')
            ->name('warrant.store')
            ->middleware('permission:create_warrant');
        Route::get('/editar/cedula/{id}', 'WarrantController@edit')
            ->name('warrant.edit')
            ->middleware('permission:update_warrant');
        Route::post('warrant/update', 'WarrantController@update')
            ->name('warrant.update')
            ->middleware('permission:update_warrant');
        Route::post('warrant/destroy', 'WarrantController@destroy')
            ->name('warrant.destroy')
            ->middleware('permission:destroy_warrant');

        //CALIDAD
        Route::get('/all/qualities', 'QualityController@getQualities')
            ->middleware('permission:list_quality');
        Route::get('Calidades', 'QualityController@index')
            ->name('quality.index')
            ->middleware('permission:list_quality');
        Route::get('crear/calidad', 'QualityController@create')
            ->name('quality.create')
            ->middleware('permission:create_quality');
        Route::post('quality/store', 'QualityController@store')
            ->name('quality.store')
            ->middleware('permission:create_quality');
        Route::get('/editar/calidad/{id}', 'QualityController@edit')
            ->name('quality.edit')
            ->middleware('permission:update_quality');
        Route::post('quality/update', 'QualityController@update')
            ->name('quality.update')
            ->middleware('permission:update_quality');
        Route::post('quality/destroy', 'QualityController@destroy')
            ->name('quality.destroy')
            ->middleware('permission:destroy_quality');

        //TYPESCRAP
        Route::get('/all/typescraps', 'TypescrapController@getTypeScraps')
            ->middleware('permission:list_typeScrap');
        Route::get('Retacerías', 'TypescrapController@index')
            ->name('typescrap.index')
            ->middleware('permission:list_typeScrap');
        Route::get('crear/retaceria', 'TypescrapController@create')
            ->name('typescrap.create')
            ->middleware('permission:create_typeScrap');
        Route::post('typescrap/store', 'TypescrapController@store')
            ->name('typescrap.store')
            ->middleware('permission:create_typeScrap');
        Route::get('/editar/retaceria/{id}', 'TypescrapController@edit')
            ->name('typescrap.edit')
            ->middleware('permission:update_typeScrap');
        Route::post('typescrap/update', 'TypescrapController@update')
            ->name('typescrap.update')
            ->middleware('permission:update_typeScrap');
        Route::post('typescrap/destroy', 'TypescrapController@destroy')
            ->name('typescrap.destroy')
            ->middleware('permission:destroy_typeScrap');
        Route::post('/typescrap/delete-multiple', 'TypescrapController@deleteMultiple');


        //UNITMEASURE
        Route::get('/all/unitmeasure', 'UnitMeasureController@getUnitMeasure')
            ->middleware('permission:list_unitMeasure');
        Route::get('Unidades', 'UnitMeasureController@index')
            ->name('unitmeasure.index')
            ->middleware('permission:list_unitMeasure');
        Route::get('crear/unidad', 'UnitMeasureController@create')
            ->name('unitmeasure.create')
            ->middleware('permission:create_unitMeasure');
        Route::post('unitmeasure/store', 'UnitMeasureController@store')
            ->name('unitmeasure.store')
            ->middleware('permission:create_unitMeasure');
        Route::get('/editar/unidad/{id}', 'UnitMeasureController@edit')
            ->name('unitmeasure.edit')
            ->middleware('permission:update_unitMeasure');
        Route::post('unitmeasure/update', 'UnitMeasureController@update')
            ->name('unitmeasure.update')
            ->middleware('permission:update_unitMeasure');
        Route::post('unitmeasure/destroy', 'UnitMeasureController@destroy')
            ->name('unitmeasure.destroy')
            ->middleware('permission:destroy_unitMeasure');
        Route::post('/unitmeasure/delete-multiple', 'UnitMeasureController@deleteMultiple');


        //GENEROS
        Route::get('/all/generos', 'GeneroController@getGeneros')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::get('generos', 'GeneroController@index')
            ->name('genero.index')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::get('crear/genero', 'GeneroController@create')
            ->name('genero.create')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('genero/store', 'GeneroController@store')
            ->name('genero.store')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::get('/editar/genero/{id}', 'GeneroController@edit')
            ->name('genero.edit')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('genero/update', 'GeneroController@update')
            ->name('genero.update')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('genero/destroy', 'GeneroController@destroy')
            ->name('genero.destroy')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('/genero/delete-multiple', 'GeneroController@deleteMultiple');

        //TALLAS
        Route::get('/all/tallas', 'TallaController@getTallas')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::get('tallas', 'TallaController@index')
            ->name('talla.index')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::get('crear/talla', 'TallaController@create')
            ->name('talla.create')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('talla/store', 'TallaController@store')
            ->name('talla.store')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::get('/editar/talla/{id}', 'TallaController@edit')
            ->name('talla.edit')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('talla/update', 'TallaController@update')
            ->name('talla.update')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('talla/destroy', 'TallaController@destroy')
            ->name('talla.destroy')
            /*->middleware('permission:list_unitMeasure')*/;
        Route::post('/talla/delete-multiple', 'TallaController@deleteMultiple');

        //ROL
        Route::get('roles', 'RoleController@index')
            ->name('role.index')
            ->middleware('permission:list_role');
        Route::post('role/store', 'RoleController@store')
            ->name('role.store')
            ->middleware('permission:create_role');
        Route::post('role/update', 'RoleController@update')
            ->name('role.update')
            ->middleware('permission:update_role');
        Route::get('role/permissions/{id}', 'RoleController@getPermissions')
            ->name('role.permissions')
            ->middleware('permission:update_role');
        Route::post('role/destroy', 'RoleController@destroy')
            ->name('role.destroy')
            ->middleware('permission:destroy_role');
        Route::get('/all/roles', 'RoleController@getRoles');
        Route::get('role/permissions/{id}', 'RoleController@getPermissions')
            ->name('role.permissions')
            ->middleware('permission:update_role');
        Route::get('/crear/rol', 'RoleController@create')
            ->name('role.create');
        Route::get('/editar/rol/{id}', 'RoleController@edit')->name('role.edit');

        //PERMISSION
        Route::get('permisos', 'PermissionController@index')->name('permission.index')
            ->middleware('permission:list_permission');
        Route::post('permission/store', 'PermissionController@store')->name('permission.store')
            ->middleware('permission:create_permission');
        Route::post('permission/update', 'PermissionController@update')->name('permission.update')
            ->middleware('permission:update_permission');
        Route::post('permission/destroy', 'PermissionController@destroy')->name('permission.destroy')
            ->middleware('permission:destroy_permission');
        Route::get('/all/permissions', 'PermissionController@getPermissions');

        // MATERIAL
        Route::get('materiales', 'MaterialController@index')->name('material.index')
            ->middleware('permission:list_material');
        Route::get('crear/material', 'MaterialController@create')->name('material.create')
            ->middleware('permission:create_material');
        Route::post('material/store', 'MaterialController@store')->name('material.store')
            ->middleware('permission:create_material');
        Route::get('editar/material/{id}', 'MaterialController@edit')->name('material.edit')
            ->middleware('permission:update_material');
        Route::post('material/update', 'MaterialController@update')->name('material.update')
            ->middleware('permission:update_material');
        Route::post('material/destroy', 'MaterialController@destroy')->name('material.destroy')
            ->middleware('permission:destroy_material');
        Route::get('/all/materials', 'MaterialController@getAllMaterials')->name('all.materials')
            ->middleware('permission:list_material');

        Route::get('listar/materiales/activos/fijos', 'MaterialController@listarActivosFijos')->name('material.actives.index')
            ->middleware('permission:listActive_material');

        Route::get('all/materials/sin/op', 'MaterialController@getAllMaterialsSinOp');
        Route::get('all/materials/op', 'MaterialController@getAllMaterialsOp');

        Route::get('materiales/activos/fijos', 'MaterialController@indexMaterialsActivos')
            ->name('invoice.materials.fijos')
            ->middleware('permission:list_invoice');
        Route::get('/all/materials/activos', 'MaterialController@getAllMaterialsActivosFijos')
            ->middleware('permission:list_invoice');

        Route::get('view/material/items/{id}', 'MaterialController@getItems')->name('material.getItems');
        Route::get('view/material/activo/items/{id}', 'MaterialController@getItemsMaterialActivo')->name('material.getItems.active');
        Route::get('view/material/all/items/{id}', 'MaterialController@getItemsMaterial')->name('material.getItemsMaterial');
        Route::get('view/material/activo/all/items/{id}', 'MaterialController@getItemsMaterialAllActivos')->name('material.getItemsMaterial.activos');
        Route::post('material/enable', 'MaterialController@enableMaterial')->name('material.enable')
            ->middleware('permission:enable_material');
        Route::post('material/disable', 'MaterialController@disableMaterial')->name('material.disable')
            ->middleware('permission:enable_material');
        Route::get('habilitar/materiales', 'MaterialController@indexEnable')->name('material.index.enable')
            ->middleware('permission:enable_material');
        Route::get('/disabled/materials', 'MaterialController@getAllMaterialsDisable')->name('disabled.materials')
            ->middleware('permission:enable_material');

        // TODO: Cambio de nombre de materiales
        Route::get('update/description/large/materials', 'MaterialController@updateDescriptionLargeMaterials');


        // TODO: Listado de materiales version 2
        Route::get('/get/data/material/v2/{numberPage}', 'MaterialController@getDataMaterials')
            ->middleware('permission:list_material');
        Route::get('/listado/materiales/v2', 'MaterialController@indexV2')
            ->name('material.indexV2')
            ->middleware('permission:list_material');
        Route::get('/enviar/material/a/tienda/{material_id}', 'MaterialController@sendMaterialToStore');
        Route::post('/traslado/guardar', 'MaterialController@guardarTraslado')->name('traslado.guardar');
        Route::get('/listado/materiales/tienda', 'MaterialController@indexMaterialStore')
            ->name('material.index.store')
            ->middleware('permission:list_material');
        Route::get('/get/data/material/store/{numberPage}', 'MaterialController@getDataMaterialStore')
            ->middleware('permission:list_material');
        Route::get('/fechas/vencimientos/material/{material_id}', 'MaterialController@getFechasVencimiento');
        Route::post('/eliminar/fechas/vencimientos/material/{id}', 'MaterialController@deleteFechasVencimiento');
        Route::get('/ubicaciones/ocupadas/{material}', 'MaterialController@ocupadas');
// Mostrar detalle de ubicación ocupada
        Route::post('/ubicaciones/obtener-detalle', 'MaterialController@obtenerDetalleUbicacion')->name('ubicaciones.detalle');

// Eliminar ubicación ocupada y devolver stock
        Route::post('/ubicaciones/eliminar-ocupada', 'MaterialController@eliminarUbicacionOcupada')->name('ubicaciones.eliminar');

        Route::get('view/stock/material/items/{id}', 'MaterialController@getItemsStockItems')->name('material.stock.getItems');
        Route::get('view/stock/material/all/items/{id}', 'MaterialController@getItemsStockItemsMaterial')->name('material.stock.getItemsMaterial');
        Route::put(
            '/stock/material/item/{item}/code',
            'MaterialController@updateCode'
        )->name('items.update.code')->middleware('permission:editarCodeItems_material');

        //AREAS
        Route::get('areas', 'AreaController@index')->name('area.index')
            ->middleware('permission:list_area');
        Route::post('area/store', 'AreaController@store')->name('area.store')
            ->middleware('permission:create_area');
        Route::post('area/update', 'AreaController@update')->name('area.update')
            ->middleware('permission:update_area');
        Route::post('area/destroy', 'AreaController@destroy')->name('area.destroy')
            ->middleware('permission:destroy_area');
        Route::get('/all/areas', 'AreaController@getAreas');

        //WAREHOUSE
        Route::get('ver/almacenes/{area}', 'WarehouseController@index')->name('warehouse.index')
            ->middleware('permission:list_warehouse');
        Route::post('warehouse/store', 'WarehouseController@store')->name('warehouse.store')
            ->middleware('permission:create_warehouse');
        Route::post('warehouse/update', 'WarehouseController@update')->name('warehouse.update')
            ->middleware('permission:update_warehouse');
        Route::post('warehouse/destroy', 'WarehouseController@destroy')->name('warehouse.destroy')
            ->middleware('permission:destroy_warehouse');
        Route::get('/all/warehouses/{id_area}', 'WarehouseController@getWarehouses');
        Route::post('warehouse/generate/visual', 'WarehouseController@generateStructure')->name('warehouse.create.visual')
            ->middleware('permission:update_warehouse');
        Route::get('/ver/estructura/almacen/{warehouse_id}', 'WarehouseController@showCreateVisual');
        Route::post('/positions/toggle-status', 'WarehouseController@toggleStatus')->name('positions.toggleStatus');


        //SHELF
        Route::get('ver/anaqueles/almacen/{almacen}/area/{area}', 'ShelfController@index')->name('shelf.index')
            ->middleware('permission:list_shelf');
        Route::post('shelf/store', 'ShelfController@store')->name('shelf.store')
            ->middleware('permission:create_shelf');
        Route::post('shelf/update', 'ShelfController@update')->name('shelf.update')
            ->middleware('permission:update_shelf');
        Route::post('shelf/destroy', 'ShelfController@destroy')->name('shelf.destroy')
            ->middleware('permission:destroy_shelf');
        Route::get('/all/shelves/{id_warehouse}', 'ShelfController@getShelves');

        //LEVEL
        Route::get('ver/niveles/anaquel/{anaquel}/almacen/{almacen}/area/{area}', 'LevelController@index')->name('level.index')
            ->middleware('permission:list_level');
        Route::post('level/store', 'LevelController@store')->name('level.store')
            ->middleware('permission:create_level');
        Route::post('level/update', 'LevelController@update')->name('level.update')
            ->middleware('permission:update_level');
        Route::post('level/destroy', 'LevelController@destroy')->name('level.destroy')
            ->middleware('permission:destroy_level');
        Route::get('/all/levels/{id_shelf}', 'LevelController@getLevels');

        //CONTAINER
        Route::get('ver/contenedores/nivel/{niveles}/anaquel/{anaqueles}/almacen/{almacen}/area/{area}', 'ContainerController@index')->name('container.index')
            ->middleware('permission:list_container');
        Route::post('container/store', 'ContainerController@store')->name('container.store')
            ->middleware('permission:create_container');
        Route::post('container/update', 'ContainerController@update')->name('container.update')
            ->middleware('permission:update_container');
        Route::post('container/destroy', 'ContainerController@destroy')->name('container.destroy')
            ->middleware('permission:destroy_container');
        Route::get('/all/containers/{id_level}', 'ContainerController@getContainers');

        //POSITION
        Route::get('ver/posiciones/contenedor/{contenedor}/nivel/{niveles}/anaquel/{anaqueles}/almacen/{almacen}/area/{area}', 'PositionController@index')->name('position.index')
            ->middleware('permission:list_position');
        Route::post('position/store', 'PositionController@store')->name('position.store')
            ->middleware('permission:create_position');
        Route::post('position/update', 'PositionController@update')->name('position.update')
            ->middleware('permission:update_position');
        Route::post('position/destroy', 'PositionController@destroy')->name('position.destroy')
            ->middleware('permission:destroy_position');
        Route::get('/all/positions/{id_container}', 'PositionController@getPositions');

        //LOCATION
        Route::get('ubicaciones', 'LocationController@index')->name('location.index')
            ->middleware('permission:list_location');
        Route::get('/all/locations', 'LocationController@getLocations');
        Route::get('/ver/materiales/ubicacion/{location_id}', 'LocationController@getMaterialsByLocation')->name('location.getMaterialsLocation');
        Route::get('/view/location/all/materials/{idLocation}', 'LocationController@getMaterialsLocation');
        Route::get('/ver/items/material/{material_id}/ubicacion/{location_id}', 'LocationController@viewItemsMaterialLocation')->name('location.getItemsMaterial');
        Route::get('/view/location/all/items/material/{idMaterial}/{idLocation}', 'LocationController@getItemsMaterialLocation');

        // ENTRY
        Route::get('entradas/retaceria', 'EntryController@indexEntryScraps')->name('entry.scrap.index')
            ->middleware('permission:list_entryScrap');
        Route::get('entradas/compra', 'EntryController@indexEntryPurchase')->name('entry.purchase.index')
            ->middleware('permission:list_entryPurchase');
        Route::get('crear/entrada/compra', 'EntryController@createEntryPurchase')->name('entry.purchase.create')
            ->middleware('permission:create_entryPurchase');
        Route::get('entradas/compra/ordenes', 'EntryController@listOrderPurchase')->name('order.purchase.list')
            ->middleware('permission:listOrder_entryPurchase');
        Route::get('crear/entrada/compra/orden/{id}', 'EntryController@createEntryOrder')->name('order.purchase.create')
            ->middleware('permission:create_entryPurchase');
        Route::get('/get/all/orders/entries', 'EntryController@getAllOrders');
        Route::get('/get/order/complete/{code}', 'EntryController@getOrderPurchaseComplete');
        Route::get('imprimir/orden/compra/{id}', 'OrderPurchaseController@printOrderPurchase')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::post('entry/purchase/order/store', 'EntryController@storeEntryPurchaseOrder')->name('entry.purchase.order.store')
            ->middleware('permission:create_entryPurchase');
        Route::get('crear/entrada/retacería', 'EntryController@createEntryScrap')->name('entry.scrap.create')
            ->middleware('permission:create_entryScrap');
        Route::post('entry_purchase/store', 'EntryController@storeEntryPurchase')->name('entry.purchase.store')
            ->middleware('permission:create_entryPurchase');
        Route::post('entry_scrap/store', 'EntryController@storeEntryScrap')->name('entry.scrap.store')
            ->middleware('permission:create_entryScrap');
        Route::get('/get/materials/entry', 'MaterialController@getJsonMaterialsEntry');

        Route::get('/get/materials/stock/items/entry', 'MaterialController@getJsonMaterialStockItemsEntry');

        Route::get('/get/materials', 'MaterialController@getJsonMaterials');
        Route::get('/get/materials/transfer', 'MaterialController@getJsonMaterialsTransfer');
        Route::get('/get/materials/quote', 'MaterialController@getJsonMaterialsQuote');
        Route::get('/get/materials/combo', 'MaterialController@getJsonMaterialsCombo');
        Route::get('/get/materials/scrap', 'MaterialController@getJsonMaterialsScrap');
        Route::get('/get/locations', 'LocationController@getJsonLocations');
        Route::get('/get/items/{id_material}', 'ItemController@getJsonItems');
        Route::get('/get/json/entries/purchase', 'EntryController@getJsonEntriesPurchase');
        Route::get('/get/entries/purchase', 'EntryController@getEntriesPurchase');
        Route::get('/get/json/entries/scrap', 'EntryController@getJsonEntriesScrap');
        Route::get('/get/json/items/{entry_id}', 'ItemController@getJsonItemsEntry');

        // TODO: Rutas para V2 de listado de Entradas por almacen
        Route::get('/get/all/entries/v2/{numberPage}', 'EntryController@getAllEntriesV2')
            ->middleware('permission:list_entryPurchase');
        Route::get('/listado/entradas/compra/v2', 'EntryController@listEntryPurchaseV2')
            ->name('entry.purchase.indexV2')
            ->middleware('permission:list_entryPurchase');

        Route::get('entrada/compra/editar/{entry}', 'EntryController@editEntryPurchase')->name('entry.purchase.edit')
            ->middleware('permission:update_entryPurchase');
        Route::get('entrada/compra/visualizar/{entry}', 'EntryController@showEntryPurchase')->name('entry.purchase.show')
            ->middleware('permission:list_entryPurchase');
        Route::post('entry_purchase/update', 'EntryController@updateEntryPurchase')->name('entry.purchase.update')
            ->middleware('permission:update_entryPurchase');
        Route::post('entry_purchase/destroy/{entry}', 'EntryController@destroyEntryPurchase')->name('entry.purchase.destroy')
            ->middleware('permission:destroy_entryPurchase');
        Route::post('/destroy/detail/{id_detail}/entry/{id_entry}', 'EntryController@destroyDetailOfEntry')
            ->middleware('permission:destroy_entryPurchase');
        Route::post('/add/materials/entry/{id_entry}', 'EntryController@addDetailOfEntry')
            ->middleware('permission:destroy_entryPurchase');

        Route::get('agregar/documentos/extras/entrada/{entry}', 'EntryController@showExtraDocumentEntryPurchase')
            ->middleware('permission:update_entryPurchase');
        Route::post('/modificar/image/ingreso/compra/{image}', 'EntryController@updateImage')
            ->middleware('permission:update_entryPurchase');
        Route::post('/eliminar/image/ingreso/compra/{image}', 'EntryController@deleteImage')
            ->middleware('permission:update_entryPurchase');
        Route::post('/guardar/images/ingreso/compra/{entry}', 'EntryController@saveImages')
            ->name('save.images.entry')
            ->middleware('permission:update_entryPurchase');

        // TODO: Rutas para V2 de listado de Ordenes de compra en Entradas por almacen
        Route::get('/get/all/orders/entrie/v2/{numberPage}', 'EntryController@getAllOrdersV2')
            ->middleware('permission:listOrder_entryPurchase');
        Route::get('/entradas/compra/ordenes/v2', 'EntryController@listOrderPurchaseV2')
            ->name('order.purchase.list.indexV2')
            ->middleware('permission:listOrder_entryPurchase');
        Route::get('/update/state/orders/with/status/', 'EntryController@updateStateOrderPurchase')
            ->middleware('permission:listOrder_entryPurchase');
        /*Route::get('exportar/reporte/ordenes/compra/v2/', 'OrderPurchaseController@exportOrderGeneralExcel')
            ->middleware('permission:list_orderPurchaseExpress');*/

        Route::get('entradas/compra/reporte', 'EntryController@indexEntryPurchaseReport')->name('entry.purchase.report.index')
            ->middleware('permission:list_entryPurchase');
        Route::post('/get/json/entries/purchase/report', 'EntryController@getJsonEntriesPurchaseReport');


        /////////////  ENTRADA POR INVENTARIO    ///////////////
        Route::get('entradas/inventario', 'EntryInventoryController@indexEntryInventory')->name('entry.inventory.index')
        ->middleware('permission:list_entryInventory');
        
        Route::get('crear/entrada/inventario', 'EntryInventoryController@createEntryInventory')->name('entry.inventory.create')
        ->middleware('permission:create_entryInventory');

        Route::post('entry_inventory/store', 'EntryInventoryController@storeEntryInventory')->name('entry.inventory.store')
        ->middleware('permission:create_entryInventory');

        Route::get('entrada/inventario/editar/{entry}', 'EntryInventoryController@editEntryInventory')->name('entry.inventory.edit')
        ->middleware('permission:update_entryInventory');

        Route::post('entry_inventory/update', 'EntryInventoryController@updateEntryInventory')->name('entry.inventory.update')
        ->middleware('permission:update_entryInventory');

        Route::post('entry_inventory/destroy/{entry}', 'EntryInventoryController@destroyEntryInventory')->name('entry.inventory.destroy')
        ->middleware('permission:destroy_entryInventory');

        Route::get('/get/json/entries/inventory', 'EntryInventoryController@getJsonEntriesInventory');
        ////////////   ENTRADA POR INVENTARIO   ///////////////


        // Reporte de ordenes de compra
        Route::get('exportar/reporte/ordenes/compra', 'OrderPurchaseController@reportOrderPurchase')
            ->name('report.order.purchase')
            ->middleware('permission:create_entryPurchase');

        // Crear retazos en almacen
        Route::get('/crear/retazos/materiales', 'EntryScrapsController@indexScrapsMaterials')
            ->name('entry.create.scrap')
            ->middleware('permission:create_entryScrap');
        Route::get('/get/json/index/materials/scrap', 'EntryScrapsController@getJsonIndexMaterialsScraps');
        Route::get('/ver/items/material/{material_id}', 'EntryScrapsController@showItemsByMaterial')
            ->middleware('permission:create_entryScrap');
        Route::get('/get/json/index/items/material/{material_id}', 'EntryScrapsController@getJsonIndexItemsMaterial');
        Route::post('scrap/store', 'EntryScrapsController@storeScrap')
            ->name('scrap.store')
            ->middleware('permission:create_entryScrap');
        Route::post('store/new/scrap', 'EntryScrapsController@storeNewScrap')
            ->name('store.new.scrap')
            ->middleware('permission:create_entryScrap');
        Route::get('/get/data/material/scrap/{material_id}', 'EntryScrapsController@getJsonDataMaterial');

        // OUTPUT
        // TODO: Rutas para V2 de listado de Ordenes de compra en Entradas por almacen
        Route::get('/get/all/outputs/requests/v2/{numberPage}', 'OutputController@getAllOutputsRequestsV2')
            ->middleware('permission:list_request');
        Route::get('/listado/solicitudes/salida/v2', 'OutputController@indexOutputRequestV2')
            ->name('output.request.indexV2')
            ->middleware('permission:list_request');

        Route::get('solicitudes/salida', 'OutputController@indexOutputRequest')
            ->name('output.request.index')
            ->middleware('permission:list_request');
        Route::get('salidas', 'OutputController@indexOutputs')
            ->name('output.confirm')
            ->middleware('permission:list_output');
        Route::get('salidas/confirmadas', 'OutputController@indexOutputsConfirmed')
            ->name('output.index.confirmed')
            ->middleware('permission:list_output');
        Route::get('crear/solicitud/', 'OutputController@createOutputRequest')
            ->name('output.request.create')
            ->middleware('permission:create_request');
        Route::get('crear/solicitud/extra/{output}', 'OutputController@createOutputRequestOrderExtra')
            ->name('output.request.extra.create')
            ->middleware('permission:create_request');
        Route::get('crear/solicitud/orden/{id_quote}', 'OutputController@createOutputRequestOrder')
            ->name('output.request.order.create')
            ->middleware('permission:create_request');
        /*Route::post('ouput/store', 'OutputController@storeOutput')
            ->name('output.request.store')
            ->middleware('permission:create_request');*/
        Route::get('/get/users', 'UserController@getUsers2');
        Route::get('/get/items/output/{id_material}', 'ItemController@getJsonItemsOutput');
        Route::get('/get/items/output/complete/{id_material}', 'ItemController@getJsonItemsOutputComplete');
        Route::get('/get/items/output/scraped/{id_material}', 'ItemController@getJsonItemsOutputScraped');
        Route::get('/get/items/transfer/{id_material}', 'ItemController@getJsonItemsTransfer');
        Route::post('output_request/store', 'OutputController@storeOutputRequest')
            ->name('output.request.store')
            ->middleware('permission:create_request');
        Route::get('/get/json/output/request', 'OutputController@getOutputRequest');

        Route::get('/get/json/output/request/sin/optimize', 'OutputController@getOutputRequestSinOp');
        Route::get('/get/json/output/request/optimize', 'OutputController@getOutputRequestOp');

        Route::get('/get/json/output/confirmed', 'OutputController@getOutputConfirmed');
        Route::get('/get/json/outputs/filters/confirmed', 'OutputController@getOutputsFilterConfirmed');
        Route::get('/get/json/items/output/{output_id}', 'OutputController@getJsonItemsOutputRequest');
        Route::get('/get/json/items/output/devolver/{output_id}', 'OutputController@getJsonItemsOutputRequestDevolver');
        Route::post('output_request/edit/execution', 'OutputController@editOutputExecution')
            ->name('output.edit.execution');

        Route::post('output_request/attend', 'OutputController@attendOutputRequest')
            ->name('output.attend')
            ->middleware('permission:attend_request|attend_requestSimple');
        Route::post('output_request/confirm', 'OutputController@confirmOutputRequest')
            ->name('output.confirmed')
            ->middleware('permission:confirm_output|confirm_requestSimple');
        Route::post('output_request/delete/total', 'OutputController@destroyTotalOutputRequest')
            ->name('output.request.destroy')
            ->middleware('permission:confirm_output');
        Route::post('/destroy/output/{id_output}/item/{id_item}', 'OutputController@destroyPartialOutputRequest')
            ->middleware('permission:confirm_output');
        Route::get('/crear/item/personalizado/{id_detail}', 'OutputController@createItemCustom')
            ->name('create.item.custom');
        Route::post('/assign/item/{item_id}/output/detail/{detail_id}', 'OutputController@assignItemToOutputDetail');
        Route::post('/return/output/{id_output}/item/{id_item}', 'OutputController@returnItemOutputDetail');

        Route::post('confirm/outputs/attend', 'OutputController@confirmAllOutputsAttend')
            ->name('output.request.confirm.all')
            ->middleware('permission:confirm_output');

        Route::post('/destroy/output/{idOutput}/material/{idMaterial}/quantity/{quantity}', 'OutputController@deleteOutputMaterialQuantity');
        Route::post('/return/output/{idOutput}/material/{idMaterial}/quantity/{quantity}', 'OutputController@returnOutputMaterialQuantity');


        // TRANSFER
        Route::get('transferencias', 'TransferController@index')
            ->name('transfer.index')
            ->middleware('permission:list_transfer');
        Route::get('crear/transferencia', 'TransferController@create')
            ->name('transfer.create')
            ->middleware('permission:list_transfer');
        Route::post('transfer/store', 'TransferController@store')
            ->name('transfer.store')
            ->middleware('permission:create_transfer');
        Route::get('/get/json/transfer', 'TransferController@getTransfers');
        Route::get('/get/json/transfer/material/{transfer_id}', 'TransferController@getJsonTransfers');
        Route::post('editar/transferencia', 'TransferController@edit')
            ->name('transfer.edit')
            ->middleware('permission:update_transfer');
        Route::post('transfer/update', 'TransferController@update')
            ->name('transfer.update')
            ->middleware('permission:update_transfer');
        Route::post('transfer/cancel', 'TransferController@cancel')
            ->name('transfer.cancel')
            ->middleware('permission:destroy_transfer');
        Route::get('ver/detalle/transferencia/{id}', 'TransferController@show')
            ->middleware('permission:list_transfer');
        Route::get('/get/json/show/transfer/{id}', 'TransferController@getShowTransfer');


        Route::get('get/warehouse/area/{area_id}', 'TransferController@getWarehouse')
            ->middleware('permission:create_transfer');
        Route::get('get/shelf/warehouse/{warehouse_id}', 'TransferController@getShelf')
            ->middleware('permission:create_transfer');
        Route::get('get/level/shelf/{shelf_id}', 'TransferController@getLevel')
            ->middleware('permission:create_transfer');
        Route::get('get/container/level/{level_id}', 'TransferController@getContainer')
            ->middleware('permission:create_transfer');
        Route::get('get/position/container/{container_id}', 'TransferController@getPosition')
            ->middleware('permission:create_transfer');

        // COTIZACIONES
        Route::get('cotizaciones', 'QuoteController@index')
            ->name('quote.index')
            ->middleware('permission:list_quote');
        Route::get('cotizaciones/totales', 'QuoteController@indexGeneral')
            ->name('quote.list.general')
            ->middleware('permission:list_quote');
        Route::get('crear/cotizacion', 'QuoteController@create')
            ->name('quote.create')
            ->middleware('permission:create_quote');
        Route::get('/select/materials', 'QuoteController@selectMaterials')
            ->middleware('permission:create_quote');
        Route::get('/get/quote/materials', 'QuoteController@getMaterials')
            ->middleware('permission:create_quote');
        Route::get('/get/quote/typeahead', 'QuoteController@getMaterialsTypeahead')
            ->middleware('permission:create_quote');
        Route::get('/select/consumables', 'QuoteController@selectConsumables')
            ->middleware('permission:create_quote');
        Route::get('/get/quote/consumables', 'QuoteController@getConsumables')
            ->middleware('permission:create_quote');
        Route::post('store/quote', 'QuoteController@store')
            ->name('quote.store')
            ->middleware('permission:create_quote');
        Route::get('/all/quotes', 'QuoteController@getAllQuotes');
        Route::get('/all/quotes/general', 'QuoteController@getAllQuotesGeneral');
        Route::get('ver/cotizacion/{quote}', 'QuoteController@show')
            ->name('quote.show')
            ->middleware('permission:list_quote');
        Route::get('editar/cotizacion/{quote}', 'QuoteController@edit')
            ->name('quote.edit')
            ->middleware('permission:update_quote');
        Route::post('update/quote', 'QuoteController@update')
            ->name('quote.update')
            ->middleware('permission:update_quote');
        Route::post('/destroy/quote/{quote}', 'QuoteController@destroy')
            ->name('quote.destroy')
            ->middleware('permission:destroy_quote');
        Route::post('/confirm/quote/{quote}', 'QuoteController@confirm')
            ->name('quote.confirm')
            ->middleware('permission:confirm_quote');
        Route::post('/send/quote/{quote}', 'QuoteController@send')
            ->name('quote.send')
            ->middleware('permission:send_quote');
        Route::post('/raise/quote/{quote}/code/{code}', 'QuoteController@raiseQuote')
            ->name('quote.raise.quote')
            ->middleware('permission:raise_quote');
        Route::post('/destroy/equipment/{id_equipment}/quote/{id_quote}', 'QuoteController@destroyEquipmentOfQuote')
            ->name('quote.destroy.equipment')
            ->middleware('permission:update_quote');
        Route::post('/update/equipment/{id_equipment}/quote/{id_quote}', 'QuoteController@updateEquipmentOfQuote')
            ->name('quote.update.equipment')
            ->middleware('permission:update_quote');
        Route::get('imprimir/cliente/{quote}', 'QuoteController@printQuoteToCustomer')
            ->middleware('permission:printCustomer_quote');
        Route::get('imprimir/interno/{quote}', 'QuoteController@printQuoteToInternal')
            ->middleware('permission:printInternal_quote');
        Route::get('elevar/cotizacion', 'QuoteController@raise')
            ->name('quote.raise')
            ->middleware('permission:showRaised_quote');
        Route::get('/all/quotes/confirmed', 'QuoteController@getAllQuotesConfirmed');
        Route::get('cotizar/soles/cotizacion/{quote}', 'QuoteController@quoteInSoles')
            ->name('quote.in.soles')
            ->middleware('permission:confirm_quote');
        Route::post('/quote/in/soles/quote/{quote}', 'QuoteController@saveQuoteInSoles')
            ->name('quote.in.soles')
            ->middleware('permission:confirm_quote');
        Route::get('ajustar/cotizacion/{quote}', 'QuoteController@adjust')
            ->middleware('permission:adjust_quote');
        Route::post('adjust/quote', 'QuoteController@adjustQuote')
            ->name('quote.adjust')
            ->middleware('permission:adjust_quote');
        Route::get('/all/quotes/deleted', 'QuoteController@getAllQuotesDeleted');
        Route::get('cotizaciones/anuladas', 'QuoteController@deleted')
            ->name('quote.deleted')
            ->middleware('permission:destroy_quote');
        Route::post('/renew/quote/{quote}', 'QuoteController@renewQuote')
            ->middleware('permission:renew_quote');
        Route::get('cotizaciones/finalizadas', 'QuoteController@closed')
            ->name('quote.closed')
            ->middleware('permission:finish_quote');
        Route::get('/all/quotes/closed', 'QuoteController@getAllQuotesClosed');
        Route::post('/finish/quote/{quote}', 'QuoteController@closeQuote')
            ->middleware('permission:finish_quote');
        Route::get('/get/contact/{customer}', 'QuoteController@getContactsByCustomer');

        Route::post('/active/quote/{quote}', 'QuoteController@activeQuote')
            ->middleware('permission:finish_quote');

        Route::post('/deselevar/quote/{quote}', 'QuoteController@deselevarQuote')
            ->middleware('permission:raise_quote');

        Route::get('editar/planos/cotizacion/{quote}', 'QuoteController@editPlanos')
            ->name('quote.edit.planos')
            ->middleware('permission:update_quote');

        Route::post('/modificar/planos/cotizacion/{image}', 'QuoteController@updatePlanos')
            ->middleware('permission:update_quote');
        Route::post('/eliminar/planos/cotizacion/{image}', 'QuoteController@deletePlanos')
            ->middleware('permission:update_quote');
        Route::post('/guardar/planos/cotizacion/{quote}', 'QuoteController@savePlanos')
            ->name('save.planos.quote')
            ->middleware('permission:update_quote');

        Route::get('/get/detraction/quote/{quote_id}', 'QuoteController@getDetractionQuote');
        Route::post('/change/detraction/quote', 'QuoteController@changeDetractionQuote')
            ->name('detraction.change')
            ->middleware('permission:update_quote');

        Route::get('/get/decimals/quote/{quote_id}', 'QuoteController@getDecimalsQuote');
        Route::post('/change/decimals/quote', 'QuoteController@changeDecimalsQuote')
            ->name('decimals.change')
            ->middleware('permission:update_quote');

        // TODO: Rutas para V2 de listado de cotizaciones GENERAL
        Route::get('/get/data/quotes/v2/{numberPage}', 'QuoteController@getDataQuotes')
            ->middleware('permission:list_quote');
        Route::get('/listado/general/cotizaciones/v2', 'QuoteController@indexV2')
            ->name('quote.general.indexV2')
            ->middleware('permission:list_quote');
        Route::get('exportar/reporte/cotizaciones/v2/', 'QuoteController@exportQuotesExcel')
            ->middleware('permission:list_quote');
        Route::get('download/reporte/cotizaciones/v2/', 'QuoteController@downloadQuotesExcel')
            ->middleware('permission:list_quote');

        // TODO: Rutas para V2 de listado de cotizaciones INDEX
        Route::get('/get/data/quotes/index/v2/{numberPage}', 'QuoteController@getDataQuotesIndex')
            ->middleware('permission:list_quote');
        Route::get('/listado/cotizaciones/v2', 'QuoteController@index2V2')
            ->name('quote.indexV2')
            ->middleware('permission:list_quote');

        // TODO: Rutas para V2 de listado de cotizaciones ELEVADAS
        Route::get('/get/data/quotes/raise/v2/{numberPage}', 'QuoteController@getDataQuotesRaise')
            ->middleware('permission:showRaised_quote');
        Route::get('/listado/cotizaciones/elevadas/v2', 'QuoteController@raiseV2')
            ->name('quote.raiseV2')
            ->middleware('permission:showRaised_quote');

        // TODO: Cambiar porcentages
        Route::post('/update/percentages/equipment/{id_equipment}/quote/{id_quote}', 'QuoteController@changePercentagesEquipment')
            ->middleware('permission:update_quote');

        Route::post('/adjust/percentages/new/equipment/{id_equipment}/quote/{id_quote}', 'QuoteController@adjustPercentagesEquipment')
            ->middleware('permission:update_quote');

        // TODO: Reemplazar cotizaciones
        Route::get('reemplazar/materiales/cotizacion/{quote}', 'QuoteController@replacement')
            ->middleware('permission:replacement_quote');
        Route::get('/replacement/material/quote/{quote}/equipment/{equipment}/equipmentMaterial/{equipmentMaterial}', 'QuoteController@saveEquipmentMaterialReplacement')
            ->middleware('permission:replacement_quote');
        Route::get('/not/replacement/material/quote/{quote}/equipment/{equipment}/equipmentMaterial/{equipmentMaterial}', 'QuoteController@saveEquipmentMaterialNotReplacement')
            ->middleware('permission:replacement_quote');
        Route::post('/save/replacement/materials/{equipment}/quote/{quote}', 'QuoteController@saveMaterialsReplacementToEquipment')
            ->middleware('permission:replacement_quote');

        // TODO: Finalizar equipos
        Route::get('finalizar/equipos/cotizacion/{quote}', 'QuoteController@finishEquipmentsQuote')
            ->middleware('permission:finishEquipment_quote');
        Route::post('/finish/equipment/{equipment}/quote/{quote}', 'QuoteController@saveFinishEquipmentsQuote')
            ->middleware('permission:finishEquipment_quote');
        Route::post('/enable/equipment/{equipment}/quote/{quote}', 'QuoteController@saveEnableEquipmentsQuote')
            ->middleware('permission:finishEquipment_quote');

        // TODO: Cotizaciones perdidas
        Route::get('cotizaciones/perdidas', 'QuoteController@indexQuoteLost')
            ->name('quote.list.lost')
            ->middleware('permission:list_quote');
        Route::get('/all/quotes/lost', 'QuoteController@getAllQuoteLost');

        // TODO: Visto bueno de finanzas y operaciones
        Route::post('/visto/bueno/finances/quote/{quote}', 'QuoteController@vistoBuenoFinancesQuote')
            ->middleware('permission:VBFinances_quote');
        Route::post('/visto/bueno/operations/quote/{quote}', 'QuoteController@vistoBuenoOperationsQuote')
            ->middleware('permission:VBOperations_quote');
        Route::get('modificar/lista/materiales/cotizacion/{quote}', 'QuoteController@modificarListaMateriales')
            ->middleware('permission:replacement_quote');
        Route::post('/update/list/equipment/{id_equipment}/quote/{id_quote}', 'QuoteController@updateListEquipmentOfQuote')
            ->name('quote.update.list.equipment')
            ->middleware('permission:update_quote');
        Route::post('/destroy/list/equipment/{id_equipment}/quote/{id_quote}', 'QuoteController@destroyListEquipmentOfQuote')
            ->name('quote.destroy.list.equipment')
            ->middleware('permission:update_quote');
        Route::post('update/list/quote', 'QuoteController@updateList')
            ->name('quote.update.list')
            ->middleware('permission:update_quote');

        // ORDER EXECUTION
        Route::get('ordenes/ejecución', 'OrderExecutionController@indexOrderExecution')
            ->name('order.execution.index')
            ->middleware('permission:list_orderExecution');
        Route::get('/all/order/execution', 'OrderExecutionController@getAllOrderExecution');
        Route::get('ordenes/ejecución/finalizadas', 'OrderExecutionController@indexOrderExecutionFinished')
            ->name('order.execution.finish')
            ->middleware('permission:list_orderExecution');
        Route::get('/all/order/execution/finish', 'OrderExecutionController@getAllOrderExecutionFinished');

        // Ordenes de ejecucion para almacen
        Route::get('/materiales/ordenes/ejecución', 'OrderExecutionController@indexExecutionAlmacen')
            ->name('order.execution.almacen');
            //->middleware('permission:list_orderExecution');
        Route::get('/get/json/materials/quote/almacen/{quote_id}', 'OrderExecutionController@getJsonMaterialsQuoteForAlmacen');
        Route::get('/get/json/materials/order/execution/almacen/{code_execution}', 'OrderExecutionController@getJsonMaterialsByQuoteExecutionForAlmacen');


        // ORDER PURCHASE
        Route::get('ordenes/compra/general', 'OrderPurchaseController@indexOrderPurchaseExpressAndNormal')
            ->name('order.purchase.general.index')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('ordenes/compra/express', 'OrderPurchaseController@indexOrderPurchaseExpress')
            ->name('order.purchase.express.index')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('crear/orden/compra/express', 'OrderPurchaseController@createOrderPurchaseExpress')
            ->name('order.purchase.express.create')
            ->middleware('permission:create_orderPurchaseExpress');
        Route::post('store/order/purchase', 'OrderPurchaseController@storeOrderPurchaseExpress')
            ->name('order.purchase.express.store')
            ->middleware('permission:create_orderPurchaseExpress');
        Route::get('/all/order/express', 'OrderPurchaseController@getAllOrderExpress');
        Route::get('/all/order/general', 'OrderPurchaseController@getAllOrderGeneral');
        Route::get('editar/orden/compra/express/{id}', 'OrderPurchaseController@editOrderPurchaseExpress')
            ->middleware('permission:update_orderPurchaseExpress');
        Route::post('update/order/purchase', 'OrderPurchaseController@updateOrderPurchaseExpress')
            ->name('order.purchase.express.update')
            ->middleware('permission:update_orderPurchaseExpress');
        Route::post('/destroy/detail/order/purchase/express/{idDetail}/material/{materialId}', 'OrderPurchaseController@destroyDetail')
            ->middleware('permission:destroy_orderPurchaseExpress');
        Route::post('/update/detail/order/purchase/express/{idDetail}', 'OrderPurchaseController@updateDetail')
            ->middleware('permission:update_orderPurchaseExpress');
        Route::get('ver/orden/compra/express/{id}', 'OrderPurchaseController@showOrderPurchaseExpress')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::post('destroy/order/purchase/express/{id}', 'OrderPurchaseController@destroyOrderPurchaseExpress')
            ->middleware('permission:update_orderPurchaseExpress');

        Route::get('ordenes/compra/normal', 'OrderPurchaseController@indexOrderPurchaseNormal')
            ->name('order.purchase.normal.index')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('crear/orden/compra/normal', 'OrderPurchaseController@createOrderPurchaseNormal')
            ->name('order.purchase.normal.create')
            ->middleware('permission:create_orderPurchaseNormal');
        Route::post('store/order/purchase/normal', 'OrderPurchaseController@storeOrderPurchaseNormal')
            ->name('order.purchase.normal.store')
            ->middleware('permission:create_orderPurchaseNormal');
        Route::get('/all/order/normal', 'OrderPurchaseController@getAllOrderNormal');
        Route::get('editar/orden/compra/normal/{id}', 'OrderPurchaseController@editOrderPurchaseNormal')
            ->middleware('permission:update_orderPurchaseNormal');
        Route::post('update/order/purchase/normal', 'OrderPurchaseController@updateOrderPurchaseNormal')
            ->name('order.purchase.normal.update')
            ->middleware('permission:update_orderPurchaseNormal');
        Route::post('/destroy/detail/order/purchase/normal/{idDetail}/material/{materialId}', 'OrderPurchaseController@destroyNormalDetail')
            ->middleware('permission:destroy_orderPurchaseNormal');
        Route::post(
            '/destroy/detail/order/purchase/normal/{idDetail}/stock-item/{stockItemId}',
            'OrderPurchaseController@destroyNormalDetail'
        )->middleware('permission:destroy_orderPurchaseNormal');
        Route::post('/update/detail/order/purchase/normal/{idDetail}', 'OrderPurchaseController@updateNormalDetail')
            ->middleware('permission:update_orderPurchaseNormal');
        Route::get('ver/orden/compra/normal/{id}', 'OrderPurchaseController@showOrderPurchaseNormal')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::post('destroy/order/purchase/normal/{id}', 'OrderPurchaseController@destroyOrderPurchaseNormal')
            ->middleware('permission:destroy_orderPurchaseNormal');

        Route::post('order_purchase/change/status/{order_id}/{status}', 'OrderPurchaseController@changeStatusOrderPurchase')
            ->middleware('permission:update_orderPurchaseNormal');

        Route::get('ordenes/compra/eliminadas', 'OrderPurchaseController@indexOrderPurchaseDelete')
            ->name('order.purchase.delete')
            ->middleware('permission:destroy_orderPurchaseNormal');
        Route::get('/all/order/delete', 'OrderPurchaseController@getOrderDeleteGeneral');
        Route::get('ver/orden/compra/eliminada/{id}', 'OrderPurchaseController@showOrderPurchaseDelete')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('imprimir/orden/compra/eliminada/{id}', 'OrderPurchaseController@printOrderPurchaseDelete')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::post('/restore/order/purchase/delete/{id}', 'OrderPurchaseController@restoreOrderPurchaseDelete')
            ->middleware('permission:destroy_orderPurchaseNormal');

        Route::get('ordenes/compra/regularizadas', 'OrderPurchaseController@indexOrderPurchaseRegularize')
            ->name('order.purchase.list.regularize')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('/all/order/purchase/regularize', 'OrderPurchaseController@getAllOrderRegularize');

        Route::get('ordenes/compra/perdidas', 'OrderPurchaseController@indexOrderPurchaseLost')
            ->name('order.purchase.list.lost')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('/all/order/purchase/lost', 'OrderPurchaseController@getAllOrderPurchaseLost');

        Route::get('/get/information/quantity/material/{material_id}', 'OrderPurchaseController@getInformationQuantityMaterial');

        // TODO: Rutas para V2 de listado de Ordenes de compra
        Route::get('/get/data/orders/general/v2/{numberPage}', 'OrderPurchaseController@getDataOrderGeneral')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('/listado/general/ordenes/compra/v2', 'OrderPurchaseController@indexV2')
            ->name('order.purchase.general.indexV2')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('exportar/reporte/ordenes/compra/v2/', 'OrderPurchaseController@exportOrderGeneralExcel')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('/get/state/order/purchase/{orderPurchase_id}', 'OrderPurchaseController@getStateOrderPurchase');
        Route::post('/change/state/order/purchase', 'OrderPurchaseController@changeStateOrderPurchase')
            ->name('state.order.purchase.change')
            ->middleware('permission:update_orderPurchaseExpress');

        // TODO: Rutas para V2 de listado de Ordenes de compra Normal y Express
        Route::get('/get/data/orders/express/v2/{numberPage}', 'OrderPurchaseController@getDataOrderExpress')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('/listado/ordenes/compra/express/v2', 'OrderPurchaseController@indexExpressV2')
            ->name('order.purchase.express.indexV2')
            ->middleware('permission:list_orderPurchaseExpress');
        Route::get('/get/data/orders/normal/v2/{numberPage}', 'OrderPurchaseController@getDataOrderNormal')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('/listado/ordenes/compra/normal/v2', 'OrderPurchaseController@indexNormalV2')
            ->name('order.purchase.normal.indexV2')
            ->middleware('permission:list_orderPurchaseNormal');

        // PROFILE
        Route::get('perfil', 'UserController@profile')
            ->name('user.profile');
        Route::post('change/image/user/{user}', 'UserController@changeImage')
            ->name('user.change.image');
        Route::post('change/settings/user/{user}', 'UserController@changeSettings')
            ->name('user.change.settings');
        Route::post('change/password/user/{user}', 'UserController@changePassword')
            ->name('user.change.password');

        // INVOICE
        Route::get('factura/compra', 'InvoiceController@indexInvoices')->name('invoice.index')
            ->middleware('permission:list_invoice');
        Route::get('factura/compra/anuladas', 'InvoiceController@indexInvoicesAnnulled')->name('invoice.index.annulled')
            ->middleware('permission:list_invoice');
        Route::get('crear/factura/compra', 'InvoiceController@createInvoice')->name('invoice.create')
            ->middleware('permission:create_invoice');
        Route::post('invoice/store', 'InvoiceController@storeInvoice')->name('invoice.store')
            ->middleware('permission:create_invoice');
        Route::post('destroy/detail/invoice/{idDetail}', 'InvoiceController@destroyDetailInvoice')
            ->middleware('permission:update_invoice');
        Route::post('destroy/total/invoice/{id}', 'InvoiceController@destroyInvoice')
            ->middleware('permission:destroy_invoice');

        Route::get('/get/json/invoices/purchase', 'InvoiceController@getJsonInvoices');
        Route::get('/get/json/invoices/purchase/annulled', 'InvoiceController@getJsonInvoicesAnnulled');
        Route::get('/get/invoices/purchase', 'InvoiceController@getInvoices');
        Route::get('/get/invoice/by/id/{id}', 'InvoiceController@getInvoiceById');
        Route::get('/get/service/by/id/{id}', 'InvoiceController@getServiceById');

        Route::get('factura/compra/editar/{entry}', 'InvoiceController@editInvoice')->name('invoice.edit')
            ->middleware('permission:update_invoice');
        Route::get('factura/compra/ver/{entry}', 'InvoiceController@verInvoice')->name('invoice.show');
        Route::post('invoice/update', 'InvoiceController@updateInvoice')->name('invoice.update')
            ->middleware('permission:update_invoice');

        // REPORT
        Route::get('report/amount/items', 'ReportController@amountInWarehouse');
        Route::get('report/excel/amount/stock', 'ReportController@excelAmountStock')->name('report.excel.amount');
        Route::get('report/excel/bd/materials', 'ReportController@excelBDMaterials')->name('report.excel.materials');
        Route::get('report/excel/bd/materials/location/{id}', 'ReportController@excelBDMaterialsByLocation')->name('report.excel.materials.location');
        Route::get('report/excel/bd/materials/warehouse/{id}', 'ReportController@excelBDMaterialsByWarehouse')->name('report.excel.materials.warehouse');
        Route::get('report/chart/quote/raised', 'ReportController@chartQuotesDollarsSoles')->name('report.chart.quote.raised');
        Route::get('report/chart/quote/view/{date_start}/{date_end}', 'ReportController@chartQuotesDollarsSolesView')->name('report.chart.quote.raised.view');
        Route::get('report/chart/expense/income', 'ReportController@chartExpensesIncomeDollarsSoles')->name('report.chart.income.expense');
        Route::get('report/chart/income/expense/view/{date_start}/{date_end}', 'ReportController@chartExpensesIncomeDollarsSolesView')->name('report.chart.income.expense.view');
        Route::get('report/chart/utilities', 'ReportController@chartUtilitiesDollarsSoles')->name('report.chart.utilities');
        Route::get('report/chart/utilities/view/{date_start}/{date_end}', 'ReportController@chartUtilitiesDollarsSolesView')->name('report.chart.utilities.view');

        Route::get('reporte/cotizaciones', 'ReportController@quotesReport')
            ->name('report.quote.index')
            ->middleware('permission:quote_report');
        Route::get('reporte/cotizacion/individual/{id}', 'ReportController@quoteIndividualReport')
            ->name('report.quote.individual')
            ->middleware('permission:quoteIndividual_report');
        Route::get('reporte/cotizaciones/resumen', 'ReportController@quoteSummaryReport')
            ->name('report.quote.summary')
            ->middleware('permission:quoteTotal_report');
        Route::get('exportar/reporte/factura', 'InvoiceController@exportInvoices')
            ->middleware('permission:list_invoice');
        Route::get('exportar/reporte/cotizaciones', 'ReportController@exportQuotesExcel')
            ->middleware('permission:quoteTotal_report');

        // SERVICIOS y ORDENES DE SERVICIOS
        Route::get('/ordenes/servicio', 'OrderServiceController@indexOrderServices')
            ->name('order.service.index')
            ->middleware('permission:list_orderService');
        Route::get('/listar/ordenes/servicio', 'OrderServiceController@listOrderServices')
            ->name('list.order.service.index')
            ->middleware('permission:list_orderService');
        Route::get('ordenes/servicio/crear', 'OrderServiceController@createOrderServices')
            ->name('order.service.create')
            ->middleware('permission:create_orderService');
        Route::get('servicios/', 'OrderServiceController@indexServices')
            ->name('service.index')
            ->middleware('permission:list_service');
        Route::post('order/service/store/', 'OrderServiceController@storeOrderServices')
            ->name('order.service.store')
            ->middleware('permission:create_orderService');
        Route::get('/all/order/services', 'OrderServiceController@getAllOrderService')
            ->middleware('permission:list_orderService');
        Route::get('/all/order/services/regularize', 'OrderServiceController@getAllOrderRegularizeService')
            ->middleware('permission:list_service');
        Route::post('destroy/order/service/{id}', 'OrderServiceController@destroyOrderService')
            ->middleware('permission:delete_orderService');
        Route::get('ver/orden/servicio/{id}', 'OrderServiceController@showOrderService')
            ->middleware('permission:list_orderService');
        Route::get('imprimir/orden/servicio/{id}', 'OrderServiceController@printOrderService')
            ->middleware('permission:list_orderService');
        Route::get('editar/orden/service/{id}', 'OrderServiceController@editOrderService')
            ->middleware('permission:update_orderService');
        Route::post('order/service/update', 'OrderServiceController@updateOrderService')
            ->name('order.service.update')
            ->middleware('permission:update_orderService');
        Route::post('/update/detail/order/service/{idDetail}', 'OrderServiceController@updateDetail')
            ->middleware('permission:update_orderService');
        Route::post('/destroy/detail/order/service/{idDetail}', 'OrderServiceController@destroyDetail')
            ->middleware('permission:delete_orderService');
        Route::get('ingresar/orden/servicio/{id}', 'OrderServiceController@regularizeOrderService')
            ->name('show.order.service')
            ->middleware('permission:regularize_orderService');
        Route::post('order/service/regularize', 'OrderServiceController@regularizePostOrderService')
            ->name('order.service.regularize')
            ->middleware('permission:regularize_orderService');

        Route::get('ordenes/servicio/regularizadas', 'OrderServiceController@indexOrderServiceRegularize')
            ->name('order.service.list.regularize')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('/all/order/service/regularize', 'OrderServiceController@getAllOrderRegularize');

        Route::get('ordenes/servicio/anuladas', 'OrderServiceController@indexOrderServiceDeleted')
            ->name('order.service.list.deleted')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('/all/order/service/deleted', 'OrderServiceController@getAllOrderDeleted');
        Route::get('ordenes/servicio/perdidas', 'OrderServiceController@indexOrderServiceLost')
            ->name('order.service.list.lost')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('/all/order/service/lost', 'OrderServiceController@getAllOrderLost');


        // NOTIFICATIONS
        Route::get('/get/notifications', 'NotificationController@getNotifications');
        Route::post('/read/notification/{id_notification}', 'NotificationController@readNotification');
        Route::post('/leer/todas/notificaciones', 'NotificationController@readAllNotifications');

        // CREDITS
        Route::get('/control/creditos', 'SupplierCreditController@indexCredits')
            ->name('index.credit.supplier');
        Route::get('/get/only/invoices/purchase', 'SupplierCreditController@getOnlyInvoicesPurchase');
        Route::get('/get/only/credits/supplier', 'SupplierCreditController@getOnlyCreditsSupplier');
        Route::post('/add/invoice/credit/{idEntry}', 'SupplierCreditController@addInvoiceToCredit');
        Route::get('/get/credit/by/id/{creditId}', 'SupplierCreditController@getCreditById');
        Route::post('credit/control/update', 'SupplierCreditController@update')
            ->name('credit.control.update');
        Route::post('credit/control/paid', 'SupplierCreditController@paid')
            ->name('credit.control.paid');
        Route::post('/cancel/pay/credit/{idCredit}', 'SupplierCreditController@cancelPayCredit');

        // Facturas Pendientes
        Route::get('/faturas/proveedores/pendientes', 'SupplierCreditController@indexInvoicesPending')
            ->name('index.invoices.pending');
        Route::get('/get/invoices/pending', 'SupplierCreditController@getInvoicesPending');
        Route::get('/get/summary/deuda/pending', 'SupplierCreditController@getSummaryDeudaPending')
            ->name('get.summary.deuda.pending');
        Route::get('/get/invoices/for/expire', 'SupplierCreditController@getInvoiceForExpire')
            ->name('get.invoices.for.expire');
        Route::get('/get/amount/invoice/current/month', 'SupplierCreditController@getAmountInvoiceCurrentMonth')
            ->name('get.amount.invoice.current.month');
        Route::get('/get/amount/invoice/general', 'SupplierCreditController@getAmountInvoiceGeneral')
            ->name('get.amount.invoice.general');
        Route::get('exportar/reporte/creditos/', 'SupplierCreditController@exportCreditsExcel');
            //->middleware('permission:quoteTotal_report');
        Route::get('/get/pays/credit/{credit_id}', 'SupplierCreditController@getPaysCredit')
            ->name('get.pays.credit');
        Route::post('/save/pay/credit/{credit_id}', 'SupplierCreditController@savePaysCredit');
        Route::post('/delete/pay/credit/{credit_pay_id}', 'SupplierCreditController@deletePayCredit');
        Route::post('/add/days/credit/{credit_id}', 'SupplierCreditController@addDaysCredit');
        Route::post('/change/status/credit/{credit_id}/{status}', 'SupplierCreditController@changeStatusCredit');

        // PAYMENT DEADLINES
        Route::get('/all/paymentDeadlines', 'PaymentDeadlineController@getPaymentDeadlines')
            ->middleware('permission:list_paymentDeadline');
        Route::get('plazos/pagos', 'PaymentDeadlineController@index')
            ->name('paymentDeadline.index')
            ->middleware('permission:list_paymentDeadline');
        Route::get('crear/plazo/pago', 'PaymentDeadlineController@create')
            ->name('paymentDeadline.create')
            ->middleware('permission:create_paymentDeadline');
        Route::post('paymentDeadline/store', 'PaymentDeadlineController@store')
            ->name('paymentDeadline.store')
            ->middleware('permission:create_paymentDeadline');
        Route::get('/editar/plazo/pago/{id}', 'PaymentDeadlineController@edit')
            ->name('paymentDeadline.edit')
            ->middleware('permission:update_paymentDeadline');
        Route::post('paymentDeadline/update', 'PaymentDeadlineController@update')
            ->name('paymentDeadline.update')
            ->middleware('permission:update_paymentDeadline');
        Route::post('paymentDeadline/destroy', 'PaymentDeadlineController@destroy')
            ->name('paymentDeadline.destroy')
            ->middleware('permission:destroy_paymentDeadline');

        // FOLLOW MATERIALS
        Route::get('/get/follow/material/{material_id}', 'FollowMaterialController@getFollowMaterial');
        Route::get('/follow/material/{material_id}', 'FollowMaterialController@followMaterial');
        Route::get('/unfollow/material/{material_id}', 'FollowMaterialController@unfollowMaterial');
        Route::get('/seguimiento/materiales', 'FollowMaterialController@index')
            ->name('follow.index')
            ->middleware('permission:list_followMaterials');
        Route::get('/get/json/follow/material', 'FollowMaterialController@getJsonFollowMaterials');
        Route::post('/dejar/seguir/{follow_id}', 'FollowMaterialController@unFollowMaterialUser');

        Route::get('/get/json/follow/output/material/{id}', 'FollowMaterialController@getJsonDetailFollowMaterial');
        Route::get('/visualizar/orden/compra/{code}', 'OrderPurchaseController@showOrderOperator');

        Route::get('/get/json/stock/all/materials', 'FollowMaterialController@getJsonStockAllMaterials');
        Route::get('/alerta/stock/materiales', 'FollowMaterialController@indexStock')
            ->name('stock.index')
            ->middleware('permission:stock_followMaterials');

        Route::get('/send/email/with/excel', 'FollowMaterialController@sendEmailWithExcel');


        // REGULARIZAR AUTOMATICAMENTE ENTRADAS DE COMPRA
        Route::get('/regularizar/automaticamente/entrada/compra/{entry_id}', 'EntryController@regularizeAutoOrderEntryPurchase')
            ->middleware('permission:create_orderPurchaseExpress');
        Route::post('store/regularize/order/purchase', 'EntryController@regularizeEntryToOrderPurchase')
            ->name('order.purchase.regularize.store')
            ->middleware('permission:create_orderPurchaseExpress');

        // REGULARIZAR AUTOMATICAMENTE ENTRADAS DE servicio
        Route::get('/regularizar/automaticamente/entrada/servicio/{entry_id}', 'OrderServiceController@regularizeAutoOrderEntryService')
            ->middleware('permission:create_orderService');
        Route::post('store/regularize/order/service', 'OrderServiceController@regularizeEntryToOrderService')
            ->name('order.service.regularize.store')
            ->middleware('permission:create_orderService');

        // REPORTE DE MATERIALES Y SUS SALIDAS
        Route::get('/reporte/material/salidas', 'OutputController@reportMaterialOutputs')
            ->name('report.materials.outputs')
            ->middleware('permission:report_output');
        Route::get('/get/json/materials/in/output', 'OutputController@getJsonMaterialsInOutput')
            ->middleware('permission:report_output');
        Route::get('/get/json/outputs/of/material/{id_material}', 'OutputController@getJsonOutputsOfMaterial')
            ->middleware('permission:report_output');
        Route::get('/get/json/outputs/of/material/optimize/{id_material}', 'OutputController@getJsonOutputsOfMaterialOp')
            ->middleware('permission:report_output');
        Route::get('/get/json/outputs/of/material/sin/optimize/{id_material}', 'OutputController@getJsonOutputsOfMaterialSinOp')
            ->middleware('permission:report_output');

        // REPORTE DE MATERIALES Y SUS ENTRADAS
        Route::get('/reporte/material/ingresos', 'EntryController@reportMaterialEntries')
            ->name('report.materials.entries')
            ->middleware('permission:report_output');
        Route::get('/get/json/materials/in/entry', 'EntryController@getJsonMaterialsInEntry')
            ->middleware('permission:report_output');
        Route::get('/get/json/entries/of/material/{id_material}', 'EntryController@getJsonEntriesOfMaterial')
            ->middleware('permission:report_output');

        Route::get('/get/json/quantity/output/material/{id_quote}/{id_material}', 'OutputController@getQuantityMaterialOutputs')
            ->middleware('permission:report_output');

        // SOLICITUD DE COMPRA OPERACION -> LOGISITICA
        Route::get('/solicitud/compra/operaciones', 'RequestPurchaseController@indexRequestPurchase')
            ->name('request.purchase.operator')
            ->middleware('permission:list_requestPurchaseOperator');
        Route::get('/crear/solicitud/compra/operaciones', 'RequestPurchaseController@createRequestPurchase')
            ->name('request.purchase.create.operator')
            ->middleware('permission:create_requestPurchaseOperator');
        Route::post('/store/request/purchase/operator', 'RequestPurchaseController@storeRequestPurchase')
            ->name('request.purchase.store.operator')
            ->middleware('permission:create_requestPurchaseOperator');
        Route::get('/editar/solicitud/compra/operaciones/{id}', 'RequestPurchaseController@editRequestPurchase')
            ->name('request.purchase.edit.operator')
            ->middleware('permission:edit_requestPurchaseOperator');
        Route::post('/update/request/purchase/operator/{id}', 'RequestPurchaseController@updateRequestPurchase')
            ->name('request.purchase.update.operator')
            ->middleware('permission:edit_requestPurchaseOperator');
        Route::post('/delete/request/purchase/operator', 'RequestPurchaseController@destroyRequestPurchase')
            ->name('request.purchase.delete.operator')
            ->middleware('permission:delete_requestPurchaseOperator');

        //PORCENTAGE QUOTES
        Route::get('/all/porcentages/quotes', 'PorcentageQuoteController@getPorcentageQuotes')
            ->middleware('permission:list_porcentageQuote');
        Route::get('porcentajes/cotizaciones', 'PorcentageQuoteController@index')
            ->name('porcentageQuote.index')
            ->middleware('permission:list_porcentageQuote');
        Route::get('crear/porcentaje', 'PorcentageQuoteController@create')
            ->name('porcentageQuote.create')
            ->middleware('permission:create_porcentageQuote');
        Route::post('porcentage/store', 'PorcentageQuoteController@store')
            ->name('porcentageQuote.store')
            ->middleware('permission:create_porcentageQuote');
        Route::get('/editar/porcentaje/cotizacion/{id}', 'PorcentageQuoteController@edit')
            ->name('porcentageQuote.edit')
            ->middleware('permission:update_porcentageQuote');
        Route::post('porcentages/update', 'PorcentageQuoteController@update')
            ->name('porcentageQuote.update')
            ->middleware('permission:update_porcentageQuote');
        Route::post('porcentages/destroy', 'PorcentageQuoteController@destroy')
            ->name('porcentageQuote.destroy')
            ->middleware('permission:destroy_porcentageQuote');

        // REPORTE DE FACTURAS POR CATEGORIAS
        Route::get('/reporte/faturas/finanzas', 'InvoiceController@reportInvoiceFinance')
            ->name('report.invoice.finance')
            ->middleware('permission:list_invoice');
        Route::get('/get/json/invoices/finance', 'InvoiceController@getJsonInvoicesFinance');

        Route::get('/reporte/faturas/finanzas/sin/orden', 'InvoiceController@reportInvoiceFinanceSinOrden')
            ->name('report.invoice.finance.sin.orden')
            ->middleware('permission:list_invoice');
        Route::get('/get/json/invoices/finance/sin/orden', 'InvoiceController@getJsonInvoicesFinanceSinOrden');


        // CRONOGRAMAS DE CONTROL DE HORAS
        Route::get('/cronogramas', 'TimelineController@showTimelines')
            ->name('index.timelines')
            ->middleware('permission:index_timeline');
        /*Route::get('/crear/cronograma', 'TimelineController@createTimelines')
            ->name('create.timeline');*/
        Route::get('/get/timeline/current', 'TimelineController@getTimelineCurrent');
        Route::get('/gestionar/cronograma/{timeline}', 'TimelineController@manageTimeline')
            ->name('manage.timeline');
        Route::get('/ver/cronograma/{timeline}', 'TimelineController@showTimeline')
            ->name('show.timeline');
        Route::get('/registrar/avances/cronograma/{timeline}', 'TimelineController@registerProgressTimeline')
            ->name('register.progress');
        Route::get('/get/timeline/forget/{date}', 'TimelineController@getTimelineForget');
        Route::get('/get/activity/forget/{id_timeline}', 'TimelineController@getActivityForget');


        Route::post('/create/activity/timeline/{id}', 'TimelineController@createNewActivity');
        Route::get('/check/timeline/for/create/{date}', 'TimelineController@checkTimelineForCreate');
        Route::post('/remove/activity/timeline/{id}', 'TimelineController@deleteActivity');
        Route::post('/save/activity/timeline/{id}', 'TimelineController@saveActivity');
        Route::post('/save/progress/activity/{id}', 'TimelineController@saveProgressActivity');
        Route::post('/assign/activity/{activity_id}/timeline/{timeline_id}', 'TimelineController@assignActivityToTimeline');
        Route::get('/print/timeline/{id_timeline}', 'TimelineController@printTimeline')
            ->name('download.timeline');

        // Cambio de Cronogramas
        Route::get('/crear/cronograma/{timeline}', 'TimelineController@createTimeline')
            ->name('create.timeline')
            ->middleware('permission:create_timeline');
        Route::post('/create/work/timeline/{id}', 'TimelineController@createNewWork');
        Route::post('/edit/work/{work_id}/timeline/{timeline_id}', 'TimelineController@editWork');
        Route::post('/create/phase/work/{id}', 'TimelineController@createNewPhase');
        Route::post('/edit/phase/{phase_id}/timeline/{timeline_id}', 'TimelineController@editPhase');
        Route::post('/create/task/phase/{id}', 'TimelineController@createNewTask');
        Route::post('/save/task/timeline/{id}', 'TimelineController@saveTask');
        Route::post('/remove/task/{id}', 'TimelineController@deleteTask');
        Route::post('/remove/phase/{id}', 'TimelineController@deletePhase');
        Route::post('/remove/work/{id}', 'TimelineController@deleteWork');
        Route::get('/revisar/cronograma/{timeline}', 'TimelineController@reviewTimeline')
            ->name('review.timeline')
            ->middleware('permission:show_timeline');
        Route::get('/revisar/avances/cronograma/{timeline}', 'TimelineController@checkProgressTimeline')
            ->name('save.progress')
            ->middleware('permission:progress_timeline');
        Route::post('/save/progress/task/{id}', 'TimelineController@saveProgressTask');
        Route::post('/assign/task/{task_id}/timeline/{timeline_id}', 'TimelineController@assignTaskToTimeline');
        Route::get('/descargar/excel/timeline/operator/{id_timeline}', 'TimelineController@downloadTimelineOperator')
            ->name('excel.operator.timeline')
            ->middleware('permission:download_timeline');
        Route::get('/descargar/excel/timeline/supervisor/{id_timeline}', 'TimelineController@downloadTimelineSupervisor')
            ->name('excel.supervisor.timeline')
            ->middleware('permission:download_timeline');
        Route::get('/descargar/excel/timeline/principal/{id_timeline}', 'TimelineController@downloadTimelinePrincipal')
            ->name('excel.principal.timeline')
            ->middleware('permission:download_timeline');
        Route::get('/get/info/work/{id}', 'TimelineController@getInfoWork');
        Route::get('/get/equipments/work/phase', 'TimelineController@getEquipmentsWorkPhase');

        // TRABAJADORES
        Route::get('/colaboradores', 'WorkerController@index')
            ->name('worker.index')
            ->middleware('permission:list_worker');
        Route::get('/get/workers/', 'WorkerController@getWorkers');
        Route::get('/registrar/colaborador', 'WorkerController@create')
            ->name('worker.create')
            ->middleware('permission:create_worker');
        Route::post('worker/store', 'WorkerController@store')
            ->name('worker.store')
            ->middleware('permission:create_worker');
            /*->middleware('permission:create_material');*/
        Route::get('editar/colaborador/{id}', 'WorkerController@edit')
            ->name('worker.edit')
            ->middleware('permission:edit_worker');
        Route::post('worker/update/{id}', 'WorkerController@update')
            ->name('worker.update')
            ->middleware('permission:edit_worker');
        Route::post('/destroy/worker/{id}', 'WorkerController@destroy')
            ->middleware('permission:destroy_worker');
        Route::get('/habilitar/colaborador', 'WorkerController@indexEnable')
            ->name('worker.enable')
            ->middleware('permission:restore_worker');
        Route::get('/get/workers/enable/', 'WorkerController@getWorkersEnable');
        Route::post('/enable/worker/{id}', 'WorkerController@enable')
            ->middleware('permission:restore_worker');
        Route::get('exportar/reporte/colaboradores/', 'WorkerController@exportWorkers')
            ->middleware('permission:edit_worker');

        //Route::get('/probar/cadenas', 'WorkerController@pruebaCadenas');

        //PORCENTAGE QUOTES
        Route::get('/all/percentages/workers', 'PercentageWorkerController@getPercentageWorkers')
            ->middleware('permission:list_percentageWorker');
        Route::get('porcentajes/recursos/humanos', 'PercentageWorkerController@index')
            ->name('percentageWorker.index')
            ->middleware('permission:list_percentageWorker');
        Route::get('crear/porcentaje/recursos/humanos', 'PercentageWorkerController@create')
            ->name('percentageWorker.create')
            ->middleware('permission:create_percentageWorker');
        Route::post('percentage/worker/store', 'PercentageWorkerController@store')
            ->name('percentageWorker.store')
            ->middleware('permission:create_percentageWorker');
        Route::get('/editar/porcentaje/recursos/humanos/{id}', 'PercentageWorkerController@edit')
            ->name('percentageWorker.edit')
            ->middleware('permission:update_percentageWorker');
        Route::post('percentage/worker/update', 'PercentageWorkerController@update')
            ->name('percentageWorker.update')
            ->middleware('permission:update_percentageWorker');
        Route::post('percentage/worker/destroy', 'PercentageWorkerController@destroy')
            ->name('percentageWorker.destroy')
            ->middleware('permission:destroy_percentageWorker');

        // CRUD Contratos
        Route::get('/all/contracts', 'ContractController@getAllContracts')
            ->middleware('permission:contract_worker');
        Route::get('contratos', 'ContractController@index')
            ->name('contract.index')
            ->middleware('permission:contract_worker');
        Route::get('crear/contrato/{worker_id}', 'ContractController@create')
            ->name('contract.create')
            ->middleware('permission:contract_worker');
        Route::get('renovar/contrato/{worker_id}', 'ContractController@renew')
            ->name('contract.renew')
            ->middleware('permission:contract_worker');
        Route::post('contract/store', 'ContractController@store')
            ->name('contract.store')
            ->middleware('permission:contract_worker');
        Route::post('contract/renew', 'ContractController@storeRenew')
            ->name('contract.storeRenew')
            ->middleware('permission:contract_worker');
        Route::get('/editar/contrato/{id}', 'ContractController@edit')
            ->name('contract.edit')
            ->middleware('permission:contract_worker');
        Route::post('contract/update', 'ContractController@update')
            ->name('contract.update')
            ->middleware('permission:contract_worker');
        Route::post('contract/destroy', 'ContractController@destroy')
            ->name('contract.destroy')
            ->middleware('permission:contract_worker');
        Route::get('/all/contracts/deleted', 'ContractController@getContractsDeleted')
            ->middleware('permission:contract_worker');
        Route::get('contratos/eliminados', 'ContractController@indexDeleted')
            ->name('contract.deleted')
            ->middleware('permission:contract_worker');
        Route::post('contract/restore', 'ContractController@restore')
            ->name('contract.restore')
            ->middleware('permission:contract_worker');
        Route::get('get/data/finish/contract/worker/{worker_id}', 'WorkerController@getDataFinishContractWorker')
            ->middleware('permission:contract_worker');
        Route::get('get/data/finish/contract/worker/edit/{worker_id}', 'WorkerController@getDataFinishContractWorkerEdit')
            ->middleware('permission:contract_worker');
        Route::post('contract/finish', 'WorkerController@finishContract')
            ->name('contract.finish')
            ->middleware('permission:contract_worker');

        Route::get('get/data/finish/contract/worker/delete/{worker_id}', 'WorkerController@getDataFinishContractWorkerDelete')
            ->middleware('permission:contract_worker');
        Route::post('contract/finish/delete', 'WorkerController@finishContractDelete')
            ->name('contract.finish.delete')
            ->middleware('permission:contract_worker');

        // CRUD Estado Civil
        Route::get('/all/civilStatuses', 'CivilStatusController@getAllCivilStatus')
            ->middleware('permission:statusCivil_worker');
        Route::get('estado/civil', 'CivilStatusController@index')
            ->name('civilStatuses.index')
            ->middleware('permission:statusCivil_worker');
        Route::get('crear/estado/civil', 'CivilStatusController@create')
            ->name('civilStatuses.create')
            ->middleware('permission:statusCivil_worker');
        Route::post('civilStatuses/store', 'CivilStatusController@store')
            ->name('civilStatuses.store')
            ->middleware('permission:statusCivil_worker');
        Route::get('/editar/estado/civil/{id}', 'CivilStatusController@edit')
            ->name('civilStatuses.edit')
            ->middleware('permission:statusCivil_worker');
        Route::post('civilStatuses/update', 'CivilStatusController@update')
            ->name('civilStatuses.update')
            ->middleware('permission:statusCivil_worker');
        Route::post('civilStatuses/destroy', 'CivilStatusController@destroy')
            ->name('civilStatuses.destroy')
            ->middleware('permission:statusCivil_worker');
        Route::get('/all/civilStatuses/deleted', 'CivilStatusController@getCivilStatusesDeleted')
            ->middleware('permission:statusCivil_worker');
        Route::get('estado/civil/eliminados', 'CivilStatusController@indexDeleted')
            ->name('civilStatuses.deleted')
            ->middleware('permission:statusCivil_worker');
        Route::post('civilStatuses/restore', 'CivilStatusController@restore')
            ->name('civilStatuses.restore')
            ->middleware('permission:statusCivil_worker');

        // CRUD Parentescos
        Route::get('/all/relationships', 'RelationshipController@getAllRelationships')
            ->middleware('permission:relationship_worker');
        Route::get('parentescos', 'RelationshipController@index')
            ->name('relationship.index')
            ->middleware('permission:relationship_worker');
        Route::get('crear/parentesco', 'RelationshipController@create')
            ->name('relationship.create')
            ->middleware('permission:relationship_worker');
        Route::post('relationship/store', 'RelationshipController@store')
            ->name('relationship.store')
            ->middleware('permission:relationship_worker');
        Route::get('/editar/parentesco/{id}', 'RelationshipController@edit')
            ->name('relationship.edit')
            ->middleware('permission:relationship_worker');
        Route::post('relationship/update', 'RelationshipController@update')
            ->name('relationship.update')
            ->middleware('permission:relationship_worker');
        Route::post('relationship/destroy', 'RelationshipController@destroy')
            ->name('relationship.destroy')
            ->middleware('permission:relationship_worker');
        Route::get('/all/relationship/deleted', 'RelationshipController@getCivilStatusesDeleted')
            ->middleware('permission:relationship_worker');
        Route::get('parentescos/eliminados', 'RelationshipController@indexDeleted')
            ->name('relationship.deleted')
            ->middleware('permission:relationship_worker');
        Route::post('relationship/restore', 'RelationshipController@restore')
            ->name('relationship.restore')
            ->middleware('permission:relationship_worker');

        // CRUD Cargos
        Route::get('/all/workFunctions', 'WorkFunctionController@getAllWorkFunctions')
            ->middleware('permission:function_worker');
        Route::get('cargos', 'WorkFunctionController@index')
            ->name('workFunctions.index')
            ->middleware('permission:function_worker');
        Route::get('crear/cargo', 'WorkFunctionController@create')
            ->name('workFunctions.create')
            ->middleware('permission:function_worker');
        Route::post('workFunctions/store', 'WorkFunctionController@store')
            ->name('workFunctions.store')
            ->middleware('permission:function_worker');
        Route::get('/editar/cargo/{id}', 'WorkFunctionController@edit')
            ->name('workFunctions.edit')
            ->middleware('permission:function_worker');
        Route::post('workFunctions/update', 'WorkFunctionController@update')
            ->name('workFunctions.update')
            ->middleware('permission:function_worker');
        Route::post('workFunctions/destroy', 'WorkFunctionController@destroy')
            ->name('workFunctions.destroy')
            ->middleware('permission:function_worker');
        Route::get('/all/workFunctions/deleted', 'WorkFunctionController@getWorkFunctionsDeleted')
            ->middleware('permission:function_worker');
        Route::get('cargos/eliminados', 'WorkFunctionController@indexDeleted')
            ->name('workFunctions.deleted')
            ->middleware('permission:function_worker');
        Route::post('workFunctions/restore', 'WorkFunctionController@restore')
            ->name('workFunctions.restore')
            ->middleware('permission:function_worker');

        // CRUD Sistemas de pension
        Route::get('/all/pensionSystems', 'PensionSystemController@getAllPensionSystems')
            ->middleware('permission:systemPension_worker');
        Route::get('sistemas/pension', 'PensionSystemController@index')
            ->name('pensionSystems.index')
            ->middleware('permission:systemPension_worker');
        Route::get('crear/sistema/pension', 'PensionSystemController@create')
            ->name('pensionSystems.create')
            ->middleware('permission:systemPension_worker');
        Route::post('pensionSystems/store', 'PensionSystemController@store')
            ->name('pensionSystems.store')
            ->middleware('permission:systemPension_worker');
        Route::get('/editar/sistema/pension/{id}', 'PensionSystemController@edit')
            ->name('pensionSystems.edit')
            ->middleware('permission:systemPension_worker');
        Route::post('pensionSystems/update', 'PensionSystemController@update')
            ->name('pensionSystems.update')
            ->middleware('permission:systemPension_worker');
        Route::post('pensionSystems/destroy', 'PensionSystemController@destroy')
            ->name('pensionSystems.destroy')
            ->middleware('permission:systemPension_worker');
        Route::get('/all/pensionSystems/deleted', 'PensionSystemController@getPensionSystemsDeleted')
            ->middleware('permission:systemPension_worker');
        Route::get('sistemas/pension/eliminados', 'PensionSystemController@indexDeleted')
            ->name('pensionSystems.deleted')
            ->middleware('permission:systemPension_worker');
        Route::post('pensionSystems/restore', 'PensionSystemController@restore')
            ->name('pensionSystems.restore')
            ->middleware('permission:systemPension_worker');

        // CRUD Feriados
        Route::get('/all/holidays', 'HolidayController@getAllHolidays')
            ->middleware('permission:holiday_worker');
        Route::get('feriados', 'HolidayController@index')
            ->name('holiday.index')
            ->middleware('permission:holiday_worker');
        Route::get('crear/feriado', 'HolidayController@create')
            ->name('holiday.create')
            ->middleware('permission:holiday_worker');
        Route::post('holiday/store', 'HolidayController@store')
            ->name('holiday.store')
            ->middleware('permission:holiday_worker');
        Route::get('/editar/feriado/{id}', 'HolidayController@edit')
            ->name('holiday.edit')
            ->middleware('permission:holiday_worker');
        Route::post('holiday/update', 'HolidayController@update')
            ->name('holiday.update')
            ->middleware('permission:holiday_worker');
        Route::post('holiday/destroy', 'HolidayController@destroy')
            ->name('holiday.destroy')
            ->middleware('permission:holiday_worker');
        Route::post('/generate/holidays', 'HolidayController@generateHolidays')
            ->name('holiday.generate')
            ->middleware('permission:holiday_worker');

        // ASISTENCIA
        Route::get('calendario/asistencia', 'AssistanceController@index')
            ->name('assistance.index');
            //->middleware('permission:systemPension_worker');
        Route::get('/check/assistance/{date}', 'AssistanceController@checkAssitanceForCreate');
        Route::get('/registrar/asistencia/{assistance}', 'AssistanceController@createAssistance')
            ->name('assistance.register');
            /*->middleware('permission:create_timeline');*/
        Route::get('/ver/asistencias/', 'AssistanceController@showAssistance')
            ->name('assistance.show');
        Route::get('/get/assistance/{month}/{year}', 'AssistanceController@getAssistancesMonthYear');
        Route::post('/store/assistance/{id_assistance}/worker/{id_worker}', 'AssistanceController@store')
            ->name('assistance.store');
        Route::post('/update/assistance/detail/{assistanceDetail_id}', 'AssistanceController@update')
            ->name('assistance.update');
        Route::post('/destroy/assistance/detail/{assistanceDetail_id}', 'AssistanceController@destroy')
            ->name('assistance.destroy');

        Route::get('/ver/horas/diarias', 'AssistanceController@showHourDiary')
            ->name('assistance.show.hour.diary');
        Route::get('/get/hour/diary/{month}/{year}', 'AssistanceController@getHourDiaryMonthYear');

        Route::get('/get/total/hours/by/worker/{id}', 'AssistanceController@getTotalHoursByWorker');

        Route::get('/ver/total/horas', 'AssistanceController@showTotalHours')
            ->name('assistance.show.total.hours');

        Route::get('/download/excel/assistance/', 'AssistanceController@exportAssistancesMonthYear')
            ->name('download.excel.assistance');

        Route::get('/download/excel/hours/diary/', 'AssistanceController@exportHourDiaryMonthYear')
            ->name('download.excel.hours.diary');

        Route::get('/download/excel/total/hours/', 'AssistanceController@exportTotalHoursWorker')
            ->name('download.excel.total.hours');

        Route::get('/ver/total/pagar', 'AssistanceController@showTotalPays')
            ->name('assistance.show.total.pays');

        Route::get('/ver/total/pagar/finanzas', 'AssistanceController@showTotalPaysAccounts')
            ->name('assistance.show.total.pays.accounts');

        Route::get('/download/excel/pagar/finanzas/', 'AssistanceController@exportTotalPaysAccounts')
            ->name('download.excel.total.pays.accounts')
            ->middleware('permission:downloadTotalPaysAccounts_assistance');

        Route::get('/get/weeks/total/pays/{year}', 'AssistanceController@getWeeksTotalPaysByYear');

        Route::get('/get/total/pays/by/year/week/', 'AssistanceController@getTotalPaysByYearWeek');
        Route::get('/get/total/pays/accounts/by/year/week/', 'AssistanceController@getTotalPaysAccountsByYearWeek');

        // TODO: Total Bruto
        Route::get('/ver/total/bruto', 'AssistanceController@showTotalBruto')
            ->name('assistance.show.total.bruto');

        Route::get('/get/weeks/total/bruto/{year}', 'AssistanceController@getWeeksTotalBrutoByYear');

        Route::get('/get/total/bruto/by/year/week/', 'AssistanceController@getTotalBrutoByYearWeek');


        // CRUD Descanso Medico
        Route::get('/all/medical/rests', 'MedicalRestController@getAllMedicalRest')
            ->middleware('permission:list_medicalRest');
        Route::get('descansos/medicos', 'MedicalRestController@index')
            ->name('medicalRest.index')
            ->middleware('permission:list_medicalRest');
        Route::get('crear/descanso/medico', 'MedicalRestController@create')
            ->name('medicalRest.create')
            ->middleware('permission:create_medicalRest');
        Route::post('medicalRest/store', 'MedicalRestController@store')
            ->name('medicalRest.store')
            ->middleware('permission:create_medicalRest');
        Route::get('/editar/descanso/medico/{id}', 'MedicalRestController@edit')
            ->name('medicalRest.edit')
            ->middleware('permission:edit_medicalRest');
        Route::post('medicalRest/update', 'MedicalRestController@update')
            ->name('medicalRest.update')
            ->middleware('permission:edit_medicalRest');
        Route::post('medicalRest/destroy', 'MedicalRestController@destroy')
            ->name('medicalRest.destroy')
            ->middleware('permission:delete_medicalRest');


        // CRUD Vacaciones
        Route::get('/all/vacations', 'VacationController@getAllVacation')
            ->middleware('permission:list_vacation');
        Route::get('vacaciones', 'VacationController@index')
            ->name('vacation.index')
            ->middleware('permission:list_vacation');
        Route::get('crear/vacaciones', 'VacationController@create')
            ->name('vacation.create')
            ->middleware('permission:create_vacation');
        Route::post('vacation/store', 'VacationController@store')
            ->name('vacation.store')
            ->middleware('permission:create_vacation');
        Route::get('/editar/vacaciones/{id}', 'VacationController@edit')
            ->name('vacation.edit')
            ->middleware('permission:edit_vacation');
        Route::post('vacation/update', 'VacationController@update')
            ->name('vacation.update')
            ->middleware('permission:edit_vacation');
        Route::post('vacation/destroy', 'VacationController@destroy')
            ->name('vacation.destroy')
            ->middleware('permission:delete_vacation');

        // CRUD Licencias
        Route::get('/all/licenses', 'LicenseController@getAllLicenses')
            ->middleware('permission:list_license');
        Route::get('licencias', 'LicenseController@index')
            ->name('license.index')
            ->middleware('permission:list_license');
        Route::get('crear/licencia', 'LicenseController@create')
            ->name('license.create')
            ->middleware('permission:create_license');
        Route::post('license/store', 'LicenseController@store')
            ->name('license.store')
            ->middleware('permission:create_license');
        Route::get('/editar/licencia/{id}', 'LicenseController@edit')
            ->name('license.edit')
            ->middleware('permission:edit_license');
        Route::post('license/update', 'LicenseController@update')
            ->name('license.update')
            ->middleware('permission:edit_license');
        Route::post('license/destroy', 'LicenseController@destroy')
            ->name('license.destroy')
            ->middleware('permission:delete_license');

        //CRUD UnpaidLicenses
        Route::get('/all/unpaid/licenses', 'UnpaidLicenseController@getAllUnpaidLicenses')
            ->middleware('permission:list_unpaidLicense');
        Route::get('licencias/sin/gozo', 'UnpaidLicenseController@index')
            ->name('unpaidLicense.index')
            ->middleware('permission:list_unpaidLicense');
        Route::get('crear/licencia/sin/gozo', 'UnpaidLicenseController@create')
            ->name('unpaidLicense.create')
            ->middleware('permission:create_unpaidLicense');
        Route::post('unpaid_license/store', 'UnpaidLicenseController@store')
            ->name('unpaidLicense.store')
            ->middleware('permission:create_unpaidLicense');
        Route::get('/editar/licencia/sin/gozo/{id}', 'UnpaidLicenseController@edit')
            ->name('unpaidLicense.edit')
            ->middleware('permission:edit_unpaidLicense');
        Route::post('unpaid_license/update', 'UnpaidLicenseController@update')
            ->name('unpaidLicense.update')
            ->middleware('permission:edit_unpaidLicense');
        Route::post('unpaid_license/destroy', 'UnpaidLicenseController@destroy')
            ->name('unpaidLicense.destroy')
            ->middleware('permission:delete_unpaidLicense');

        // CRUD Permisos
        Route::get('/all/permits', 'PermitController@getAllPermits')
            ->middleware('permission:list_permit');
        Route::get('permisos/trabajadores', 'PermitController@index')
            ->name('permit.index')
            ->middleware('permission:list_permit');
        Route::get('crear/permiso', 'PermitController@create')
            ->name('permit.create')
            ->middleware('permission:create_permit');
        Route::post('permit/store', 'PermitController@store')
            ->name('permit.store')
            ->middleware('permission:create_permit');
        Route::get('/editar/permiso/{id}', 'PermitController@edit')
            ->name('permit.edit')
            ->middleware('permission:edit_permit');
        Route::post('permit/update', 'PermitController@update')
            ->name('permit.update')
            ->middleware('permission:edit_permit');
        Route::post('permit/destroy', 'PermitController@destroy')
            ->name('permit.destroy')
            ->middleware('permission:delete_permit');

        // CRUD Permisos_Hora
        Route::get('/all/permits_hours', 'PermitHourController@getAllPermits')
            ->middleware('permission:list_permitHour');
        Route::get('permisosxhora/trabajadores', 'PermitHourController@index')
            ->name('permit_hour.index')
            ->middleware('permission:list_permitHour');
        Route::get('crear/permisoxhora', 'PermitHourController@create')
            ->name('permit_hour.create')
            ->middleware('permission:create_permitHour');
        Route::post('permit/storexhour', 'PermitHourController@store')
            ->name('permit_hour.store')
            ->middleware('permission:create_permitHour');
        Route::get('/editar/permisoxhora/{id}', 'PermitHourController@edit')
            ->name('permit_hour.edit')
            ->middleware('permission:edit_permitHour');
        Route::post('permitxhour/update', 'PermitHourController@update')
            ->name('permit_hour.update')
            ->middleware('permission:edit_permitHour');
        Route::post('permitxhour/destroy', 'PermitHourController@destroy')
            ->name('permit_hour.destroy')
            ->middleware('permission:destroy_permitHour');


        // CRUD ReasonSuspension
        Route::get('/all/reasonSuspensions', 'ReasonSuspensionController@getAllReasonSuspensions')
            ->middleware('permission:enable_suspension');
        Route::get('razones/suspension', 'ReasonSuspensionController@index')
            ->name('reasonSuspension.index')
            ->middleware('permission:enable_suspension');
        Route::get('crear/razon/suspension', 'ReasonSuspensionController@create')
            ->name('reasonSuspension.create')
            ->middleware('permission:enable_suspension');
        Route::post('reasonSuspension/store', 'ReasonSuspensionController@store')
            ->name('reasonSuspension.store')
            ->middleware('permission:enable_suspension');
        Route::get('/editar/razon/suspension/{id}', 'ReasonSuspensionController@edit')
            ->name('reasonSuspension.edit')
            ->middleware('permission:enable_suspension');
        Route::post('reasonSuspension/update', 'ReasonSuspensionController@update')
            ->name('reasonSuspension.update')
            ->middleware('permission:enable_suspension');
        Route::post('reasonSuspension/destroy', 'ReasonSuspensionController@destroy')
            ->name('reasonSuspension.destroy')
            ->middleware('permission:enable_suspension');

        // CRUD Suspension
        Route::get('/all/suspensions', 'SuspensionController@getAllSuspensions')
            ->middleware('permission:list_suspension');
        Route::get('suspensiones', 'SuspensionController@index')
            ->name('suspension.index')
            ->middleware('permission:list_suspension');
        Route::get('crear/suspension', 'SuspensionController@create')
            ->name('suspension.create')
            ->middleware('permission:create_suspension');
        Route::post('suspension/store', 'SuspensionController@store')
            ->name('suspension.store')
            ->middleware('permission:create_suspension');
        Route::get('/editar/suspension/{id}', 'SuspensionController@edit')
            ->name('suspension.edit')
            ->middleware('permission:edit_suspension');
        Route::post('suspension/update', 'SuspensionController@update')
            ->name('suspension.update')
            ->middleware('permission:edit_suspension');
        Route::post('suspension/destroy', 'SuspensionController@destroy')
            ->name('suspension.destroy')
            ->middleware('permission:delete_suspension');

        // JORNADAS (WORKING_DAYS)
        Route::get('/registrar/jornadas/trabajo/', 'WorkingDayController@create')
            ->name('workingDay.create');
        Route::post('/create/working/day/{id}', 'WorkingDayController@store')
            ->name('workingDay.store');
        Route::post('/update/working/day/{id}', 'WorkingDayController@update')
            ->name('workingDay.update');
        Route::post('/destroy/working/day/{id}', 'WorkingDayController@destroy')
            ->name('workingDay.destroy');

        // AREA WORKER
        Route::get('/all/areaWorkers', 'AreaWorkerController@getAreas')
            ->middleware('permission:list_areaWorker');
        Route::get('areas/empresa', 'AreaWorkerController@index')
            ->name('areaWorker.index')
            ->middleware('permission:list_areaWorker');
        Route::get('crear/area/empresa', 'AreaWorkerController@create')
            ->name('areaWorker.create')
            ->middleware('permission:create_areaWorker');
        Route::post('areaWorker/store', 'AreaWorkerController@store')
            ->name('areaWorker.store')
            ->middleware('permission:create_areaWorker');
        Route::get('/editar/area/empresa/{id}', 'AreaWorkerController@edit')
            ->name('areaWorker.edit')
            ->middleware('permission:update_areaWorker');
        Route::post('areaWorker/update', 'AreaWorkerController@update')
            ->name('areaWorker.update')
            ->middleware('permission:update_areaWorker');
        Route::post('areaWorker/destroy', 'AreaWorkerController@destroy')
            ->name('areaWorker.destroy')
            ->middleware('permission:destroy_areaWorker');

        // OUTPUT SIMPLES
        Route::get('solicitudes/area/', 'OutputController@indexOutputSimple')
            ->name('output.simple.index')
            ->middleware('permission:list_requestSimple');
        Route::get('mis/solicitudes/area/', 'OutputController@indexMyOutputSimple')
            ->name('output.simple.my.index')
            ->middleware('permission:myRequest_requestSimple');
        Route::get('crear/solicitud/area', 'OutputController@createOutputSimple')
            ->name('output.simple.create')
            ->middleware('permission:create_requestSimple');
        Route::get('crear/solicitud/area/activos', 'OutputController@createOutputSimpleActivo')
            ->name('output.simple.create.activos')
            ->middleware('permission:create_requestSimple');
        Route::post('output/area/store', 'OutputController@storeOutputSimple')
            ->name('output.simple.store')
            ->middleware('permission:create_requestSimple');

        Route::get('/get/json/output/simple', 'OutputController@getOutputSimple');
        Route::get('/get/json/my/output/simple/', 'OutputController@getMyOutputSimple');
        Route::get('/get/json/items/output/simple/{output_id}', 'OutputController@getJsonItemsOutputSimple');
        Route::get('/get/json/items/output/simple/devolver/{output_id}', 'OutputController@getJsonItemsOutputSimpleDevolver');

        Route::post('output/simple/attend', 'OutputController@attendOutputSimple')
            ->name('output.simple.attend')
            ->middleware('permission:attend_requestSimple');
        Route::post('output/simple/confirm', 'OutputController@confirmOutputSimple')
            ->name('output.simple.confirmed')
            ->middleware('permission:confirm_requestSimple');

        Route::post('output/area/delete/total', 'OutputController@destroyTotalOutputSimple')
            ->name('output.simple.destroy')
            ->middleware('permission:delete_requestSimple');
        Route::post('/destroy/output/simple/{id_output}/item/{id_item}', 'OutputController@destroyPartialOutputSimple')
            ->middleware('permission:delete_requestSimple');

        Route::post('/return/output/simple/{id_output}/item/{id_item}', 'OutputController@returnItemOutputSimpleDetail');

        Route::post('confirm/outputs/simple/attend', 'OutputController@confirmAllOutputSimpleAttend')
            ->name('output.simple.confirm.all')
            ->middleware('permission:confirm_requestSimple');

        Route::post('output/simple/edit/description', 'OutputController@editOutputSimpleDescription')
            ->name('output.simple.edit.description');

        Route::get('/reporte/material/salidas/area', 'OutputController@reportMaterialOutputsSimple')
            ->name('output.simple.report')
            ->middleware('permission:report_requestSimple');
        Route::get('/get/json/materials/in/output/simple', 'OutputController@getJsonMaterialsInOutputSimple')
            ->middleware('permission:report_requestSimple');
        Route::get('/get/json/outputs/simple/of/material/{id_material}', 'OutputController@getJsonOutputsSimpleOfMaterial')
            ->middleware('permission:report_requestSimple');
        //RUTAS PARA REPORTE DE SALIDAS POR AREA
        Route::get('/reporte/material/salidas/xarea', 'OutputController@reportMaterialByAreaOutputsSimple')
            ->name('output.simple.reportByArea')
            ->middleware('permission:report_request');
        Route::get('/get/json/outputs/simple/of/materialxarea/{id_area}', 'OutputController@getJsonOutputsSimpleOfMaterialByArea')
            ->middleware('permission:report_request');
        Route::get('/get/json/areas/in/output', 'OutputController@getJsonAreasInOutputSimple');
        Route::get('/get/json/items/output/areas/{output_id}', 'OutputController@getJsonItemsOutputArea');


        // Descuentos
        Route::get('/all/discounts', 'DiscountController@getAllDiscounts')
            ->middleware('permission:list_discount');
        Route::get('descuentos', 'DiscountController@index')
            ->name('discount.index')
            ->middleware('permission:list_discount');
        Route::get('crear/descuento', 'DiscountController@create')
            ->name('discount.create')
            ->middleware('permission:create_discount');
        Route::post('discount/store', 'DiscountController@store')
            ->name('discount.store')
            ->middleware('permission:create_discount');
        Route::get('/editar/descuento/{id}', 'DiscountController@edit')
            ->name('discount.edit')
            ->middleware('permission:edit_discount');
        Route::post('discount/update', 'DiscountController@update')
            ->name('discount.update')
            ->middleware('permission:edit_discount');
        Route::post('discount/destroy', 'DiscountController@destroy')
            ->name('discount.destroy')
            ->middleware('permission:destroy_discount');

        // Reembolso
        Route::get('/all/refunds', 'RefundController@getAllRefund')
            ->middleware('permission:list_refund');
        Route::get('reembolsos', 'RefundController@index')
            ->name('refund.index')
            ->middleware('permission:list_refund');
        Route::get('crear/reembolso', 'RefundController@create')
            ->name('refund.create')
            ->middleware('permission:create_refund');
        Route::post('refund/store', 'RefundController@store')
            ->name('refund.store')
            ->middleware('permission:create_refund');
        Route::get('/editar/reembolso/{id}', 'RefundController@edit')
            ->name('refund.edit')
            ->middleware('permission:edit_refund');
        Route::post('refund/update', 'RefundController@update')
            ->name('refund.update')
            ->middleware('permission:edit_refund');
        Route::post('refund/destroy', 'RefundController@destroy')
            ->name('refund.destroy')
            ->middleware('permission:destroy_refund');

        // Préstamos
        Route::get('/all/loans', 'LoanController@getAllLoan')
            ->middleware('permission:list_loan');
        Route::get('/all/dues/loan/{id}', 'LoanController@getAllDuesLoan')
            ->middleware('permission:list_loan');
        Route::get('prestamos', 'LoanController@index')
            ->name('loan.index')
            ->middleware('permission:list_loan');
        Route::get('crear/prestamo', 'LoanController@create')
            ->name('loan.create')
            ->middleware('permission:create_loan');
        Route::post('loan/store', 'LoanController@store')
            ->name('loan.store')
            ->middleware('permission:create_loan');
        Route::get('/editar/prestamo/{id}', 'LoanController@edit')
            ->name('loan.edit')
            ->middleware('permission:edit_loan');
        Route::post('loan/update', 'LoanController@update')
            ->name('loan.update')
            ->middleware('permission:edit_loan');
        Route::post('loan/destroy', 'LoanController@destroy')
            ->name('loan.destroy')
            ->middleware('permission:destroy_loan');

        // Gratificaciones
        Route::get('/all/period/gratifications/', 'GratificationController@getAllPeriodGratifications')
            ->middleware('permission:list_gratification');
        Route::get('/all/gratifications/by/period/{period}', 'GratificationController@getAllGratificationsByPeriod')
            ->middleware('permission:list_gratification');
        Route::get('gratificaciones', 'GratificationController@index')
            ->name('gratification.index')
            ->middleware('permission:list_gratification');
        Route::get('crear/gratificacion/{period}', 'GratificationController@create')
            ->name('gratification.create')
            ->middleware('permission:create_gratification');
        Route::post('gratification/store', 'GratificationController@store')
            ->name('gratification.store')
            ->middleware('permission:create_gratification');
        Route::post('gratification/period/store', 'GratificationController@storePeriod')
            ->name('gratification.period.store')
            ->middleware('permission:create_gratification');
        Route::post('gratification/period/update', 'GratificationController@updatePeriod')
            ->name('gratification.period.update')
            ->middleware('permission:edit_gratification');
        Route::post('gratification/period/destroy', 'GratificationController@destroyPeriod')
            ->name('gratification.period.destroy')
            ->middleware('permission:destroy_gratification');
        Route::get('/editar/gratificacion/{id}', 'GratificationController@edit')
            ->name('gratification.edit')
            ->middleware('permission:edit_gratification');
        Route::post('gratification/update', 'GratificationController@update')
            ->name('gratification.update')
            ->middleware('permission:edit_gratification');
        Route::post('gratification/destroy', 'GratificationController@destroy')
            ->name('gratification.destroy')
            ->middleware('permission:destroy_gratification');

        // Renta de Quinta Categoria
        Route::get('/all/workers/not/fifthCategory', 'FifthCategoryController@getWorkers')
            ->middleware('permission:list_fifthCategory');
        Route::get('/all/workers/fifthCategory/', 'FifthCategoryController@getWorkersFifthCategory')
            ->middleware('permission:list_fifthCategory');
        Route::get('/all/fifthCategory/by/worker/{worker}', 'FifthCategoryController@getAllFifthCategoryByWorkers')
            ->middleware('permission:list_fifthCategory');
        Route::get('/renta/quinta/categoria', 'FifthCategoryController@index')
            ->name('fifthCategory.index')
            ->middleware('permission:list_fifthCategory');
        Route::get('crear/renta/quinta/categoria/{worker}', 'FifthCategoryController@create')
            ->name('fifthCategory.create')
            ->middleware('permission:create_fifthCategory');
        Route::post('fifthCategory/store', 'FifthCategoryController@storeWorkerFifthCategory')
            ->name('fifthCategory.store')
            ->middleware('permission:create_fifthCategory');
        Route::post('fifthCategory/destroy', 'FifthCategoryController@destroyWorkerFifthCategory')
            ->name('fifthCategory.destroy')
            ->middleware('permission:destroy_fifthCategory');

        Route::post('fifthCategory/worker/store', 'FifthCategoryController@store')
            ->name('fifthCategory.worker.store')
            ->middleware('permission:create_fifthCategory');
        Route::post('fifthCategory/worker/update', 'FifthCategoryController@update')
            ->name('fifthCategory.worker.update')
            ->middleware('permission:edit_fifthCategory');
        Route::post('fifthCategory/worker/destroy', 'FifthCategoryController@destroy')
            ->name('fifthCategory.worker.destroy')
            ->middleware('permission:destroy_fifthCategory');

        // Pension de Alimentos
        Route::get('/all/workers/alimony/', 'AlimonyController@getWorkersAlimony')
            ->middleware('permission:list_alimony');
        Route::get('/pension/alimentos/', 'AlimonyController@index')
            ->name('alimony.index')
            ->middleware('permission:list_alimony');
        Route::get('ver/pension/alimentos/{worker}', 'AlimonyController@create')
            ->name('alimony.create')
            ->middleware('permission:create_alimony');
        Route::post('alimony/worker/store', 'AlimonyController@store')
            ->name('alimony.worker.store')
            ->middleware('permission:create_alimony');
        Route::post('alimony/worker/update', 'AlimonyController@update')
            ->name('alimony.worker.update')
            ->middleware('permission:edit_alimony');
        Route::post('alimony/worker/destroy', 'AlimonyController@destroy')
            ->name('alimony.worker.destroy')
            ->middleware('permission:destroy_alimony');

        // TODO: Rutas para los regimenes de trabajo
        Route::get('/registrar/regimenes/trabajo/', 'RegimeController@create')
            ->name('regime.create');
        Route::post('/create/regime', 'RegimeController@store')
            ->name('regime.store');
        Route::post('/update/regime/{id}', 'RegimeController@update')
            ->name('regime.update');
        Route::post('/destroy/regime/{id}', 'RegimeController@destroy')
            ->name('regime.destroy');
        Route::get('/get/workings/day/by/regime/{id_regime}', 'RegimeController@getWorkingsDayByRegime');
        Route::post('/update/details/regime/{id}', 'RegimeController@updateDetailsRegime');


        // TODO: Rutas para generar boletas
        Route::get('/boletas/pago/trabajadores/', 'BoletaController@indexPaySlip')
            ->name('paySlip.index')
            ->middleware('permission:list_paySlip');
        Route::get('/ver/boletas/semanales/{worker_id}', 'BoletaController@indexBoletasSemanales')
            ->name('paySlip.boletas.semanales')
            ->middleware('permission:list_paySlip');
        Route::get('/get/boletas/worker/{boleta_id}', 'BoletaController@getBoletaworker')
            ->middleware('permission:list_paySlip');
        Route::get('/imprimir/boleta/semanal/{boleta_id}', 'BoletaController@imprimirBoletaSemanal')
            ->name('paySlip.print.semanal')
            ->middleware('permission:list_paySlip');
        Route::get('/ver/boleta/semanal/{boleta_id}', 'BoletaController@verBoletaSemanal')
            ->name('paySlip.show.semanal')
            ->middleware('permission:list_paySlip');
        Route::get('/generar/boleta/trabajadores/', 'BoletaController@createBoletaByWorker')
            ->name('paySlip.create')
            ->middleware('permission:create_paySlip');
        Route::get('/generate/boleta/worker', 'BoletaController@generateBoletaWorker')
            ->name('boleta.generate.worker')/*
            ->middleware('permission:edit_gratification')*/;
        Route::get('/get/years/of/system/', 'DateDimensionController@getYearsOfSystem');
        Route::get('/get/months/of/year/{year}', 'DateDimensionController@getMonthsOfYear');
        Route::get('/get/weeks/of/month/{month}/year/{year}', 'DateDimensionController@getWeeksOfMonthsOfYear');
        Route::get('/save/boleta/worker/month', 'BoletaController@saveBoletaWorkerMonthly')
            ->middleware('permission:create_paySlip');
        Route::get('/save/boleta/worker/week', 'BoletaController@saveBoletaWorkerWeekly')
            ->middleware('permission:create_paySlip');
        Route::get('/get/workers/boletas/', 'WorkerController@getWorkersBoleta');

        // TODO: Ruta para poblar la dimension tiempo, solo usarse una vez
        //Route::get('/populate/date/dimension', 'DateDimensionController@populateDateDimension');
        Route::get('/populate/date/dimension', function () {
            abort_unless(app()->environment('local'), 403);
            app(\App\Services\DateDimensionService::class)->populate(true); // force
            return 'OK';
        });

        // TODO: Ruta para hacer pruebas en produccion para resolver las cantidades
        Route::get('/prueba/cantidades/', 'OrderPurchaseController@pruebaCantidades');
        Route::get('/prueba/bd/', 'OrderPurchaseController@pruebaBD');
        Route::get('/modificando/takens/', 'OutputController@modificandoMaterialesTomados');

        Route::get('/test/server/side', 'OutputController@getOutputRequestServerSide')->name('test.server.side');
        Route::get('solicitudes/server/side', 'OutputController@indexOutputRequestServerside');

        Route::get('/test/merge/pdfs', 'PdfsController@mergePdfs')->name('merge.pdfs');
        Route::get('/test/save/pdfs', 'PdfsController@printQuote')->name('print.pdfs');

        // TODO: Rutas para OrderPurchaseInvoices
        Route::get('ordenes/compra/finanzas', 'OrderPurchaseFinanceController@indexOrderPurchaseFinance')
            ->name('order.purchase.finance.index')
            ->middleware('permission:list_orderPurchaseFinance');
        Route::get('crear/orden/compra/finanzas', 'OrderPurchaseFinanceController@createOrderPurchaseFinance')
            ->name('order.purchase.finance.create')
            ->middleware('permission:create_orderPurchaseFinance');
        Route::post('store/order/purchase/finance', 'OrderPurchaseFinanceController@storeOrderPurchaseFinance')
            ->name('order.purchase.finance.store')
            ->middleware('permission:create_orderPurchaseFinance');
        Route::get('/all/order/finance', 'OrderPurchaseFinanceController@getAllOrderFinance');
        Route::get('editar/orden/compra/finanza/{id}', 'OrderPurchaseFinanceController@editOrderPurchaseFinance')
            ->middleware('permission:update_orderPurchaseFinance');
        Route::post('update/order/purchase/finance', 'OrderPurchaseFinanceController@updateOrderPurchaseFinance')
            ->name('order.purchase.finance.update')
            ->middleware('permission:update_orderPurchaseFinance');
        Route::post('/destroy/detail/order/purchase/finance/{idDetail}', 'OrderPurchaseFinanceController@destroyFinanceDetail')
            ->middleware('permission:destroy_orderPurchaseFinance');
        Route::post('/update/detail/order/purchase/finance/{idDetail}', 'OrderPurchaseFinanceController@updateFinanceDetail')
            ->middleware('permission:update_orderPurchaseFinance');
        Route::get('ver/orden/compra/finanzas/{id}', 'OrderPurchaseFinanceController@showOrderPurchaseFinance')
            ->name('show.order.purchase.finance')
            ->middleware('permission:list_orderPurchaseFinance');
        Route::post('destroy/order/purchase/finance/{id}', 'OrderPurchaseFinanceController@destroyOrderPurchaseFinance')
            ->middleware('permission:destroy_orderPurchaseFinance');

        Route::post('order_purchase/finance/change/status/{order_id}/{status}', 'OrderPurchaseFinanceController@changeStatusOrderPurchaseFinance')
            ->middleware('permission:update_orderPurchaseFinance');

        Route::get('ordenes/compra/finanzas/eliminadas', 'OrderPurchaseFinanceController@indexOrderPurchaseFinanceDelete')
            ->name('order.purchase.finance.delete')
            ->middleware('permission:destroy_orderPurchaseFinance');
        Route::get('/all/order/finance/delete', 'OrderPurchaseFinanceController@getOrderDeleteFinance');
        Route::get('ver/orden/compra/finanzas/eliminada/{id}', 'OrderPurchaseFinanceController@showOrderPurchaseFinanceDelete')
            ->middleware('permission:list_orderPurchaseFinance');
        Route::get('imprimir/orden/compra/finanza/{id}', 'OrderPurchaseFinanceController@printOrderPurchaseFinance')
            ->middleware('permission:list_orderPurchaseNormal');
        Route::get('imprimir/orden/compra/finanza/eliminada/{id}', 'OrderPurchaseFinanceController@printOrderPurchaseFinanceDelete')
            ->middleware('permission:list_orderPurchaseFinance');
        Route::post('/restore/order/purchase/finance/delete/{id}', 'OrderPurchaseFinanceController@restoreOrderPurchaseFinanceDelete')
            ->middleware('permission:destroy_orderPurchaseFinance');

        Route::get('ordenes/compra/finanzas/regularizadas', 'OrderPurchaseFinanceController@indexOrderPurchaseFinanceRegularize')
            ->name('order.purchase.finance.list.regularize')
            ->middleware('permission:list_orderPurchaseFinance');
        Route::get('/all/order/purchase/finance/regularize', 'OrderPurchaseFinanceController@getAllOrderRegularizeFinance');

        Route::get('ordenes/compra/finanzas/perdidas', 'OrderPurchaseFinanceController@indexOrderPurchaseFinanceLost')
            ->name('order.purchase.finance.list.lost')
            ->middleware('permission:list_orderPurchaseFinance');
        Route::get('/all/order/purchase/finance/lost', 'OrderPurchaseFinanceController@getAllOrderPurchaseFinanceLost');

        Route::get('/regularizar/automaticamente/entrada/compra/finanzas/{entry_id}', 'OrderPurchaseFinanceController@regularizeAutoOrderEntryPurchaseFinance')
            ->middleware('permission:create_orderPurchaseFinance');
        Route::post('store/regularize/order/purchase/finance', 'OrderPurchaseFinanceController@regularizeEntryToOrderPurchaseFinance')
            ->name('order.purchase.finance.regularize.store')
            ->middleware('permission:create_orderPurchaseFinance');

        // TODO: Bills
        Route::get('/all/bills', 'BillController@getAllBills')
            ->middleware('permission:list_bill');
        Route::get('tipos/gastos', 'BillController@index')
            ->name('bill.index')
            ->middleware('permission:list_bill');
        Route::get('crear/tipo/gasto', 'BillController@create')
            ->name('bill.create')
            ->middleware('permission:create_bill');
        Route::post('bill/store', 'BillController@store')
            ->name('bill.store')
            ->middleware('permission:create_bill');
        Route::get('/editar/tipo/gasto/{id}', 'BillController@edit')
            ->name('bill.edit')
            ->middleware('permission:update_bill');
        Route::post('bill/update', 'BillController@update')
            ->name('bill.update')
            ->middleware('permission:update_bill');
        Route::post('bill/destroy', 'BillController@destroy')
            ->name('bill.destroy')
            ->middleware('permission:destroy_bill');

        // TODO: Expenses
        Route::get('/all/expenses', 'ExpenseController@getAllExpenses')
            ->middleware('permission:list_expense');
        Route::get('/rendicion/gastos/general', 'ExpenseController@index')
            ->name('expense.index')
            ->middleware('permission:list_expense');
        Route::get('crear/gasto', 'ExpenseController@create')
            ->name('expense.create')
            ->middleware('permission:create_expense');
        Route::post('expense/store', 'ExpenseController@store')
            ->name('expense.store')
            ->middleware('permission:create_expense');
        Route::get('/editar/gasto/{id}', 'ExpenseController@edit')
            ->name('expense.edit')
            ->middleware('permission:update_expense');
        Route::post('expense/update', 'ExpenseController@update')
            ->name('expense.update')
            ->middleware('permission:update_expense');
        Route::post('expense/destroy', 'ExpenseController@destroy')
            ->name('expense.destroy')
            ->middleware('permission:destroy_expense');
        Route::get('/reporte/rendicion/gastos/', 'ExpenseController@report')
            ->name('expense.report')
            ->middleware('permission:report_expense');
        Route::get('/generate/report/expense/', 'ExpenseController@reportExpenses')
            ->middleware('permission:report_expense');
        Route::get('/descargar/excel/rendicion/gastos/', 'ExpenseController@downloadExpenses')
            ->middleware('permission:report_expense');

        // Bonos Especiales
        Route::get('/all/bonus', 'SpecialBonusController@getAllBonus')
            ->middleware('permission:list_bonusRisk');
        Route::get('bonos/especiales', 'SpecialBonusController@index')
            ->name('bonusRisk.index')
            ->middleware('permission:list_bonusRisk');
        Route::get('crear/bono/especial', 'SpecialBonusController@create')
            ->name('bonusRisk.create')
            ->middleware('permission:create_bonusRisk');
        Route::post('bonusRisk/store', 'SpecialBonusController@store')
            ->name('bonusRisk.store')
            ->middleware('permission:create_bonusRisk');
        Route::get('/editar/bono/especial/{id}', 'SpecialBonusController@edit')
            ->name('bonusRisk.edit')
            ->middleware('permission:edit_bonusRisk');
        Route::post('bonusRisk/update', 'SpecialBonusController@update')
            ->name('bonusRisk.update')
            ->middleware('permission:edit_bonusRisk');
        Route::post('bonusRisk/destroy', 'SpecialBonusController@destroy')
            ->name('bonusRisk.destroy')
            ->middleware('permission:destroy_bonusRisk');
        Route::get('/reporte/bonos/especiales/', 'SpecialBonusController@report')
            ->name('bonusRisk.report')
            ->middleware('permission:report_bonusRisk');
        Route::get('/generate/report/bonus/', 'SpecialBonusController@reportBonuses')
            ->middleware('permission:report_bonusRisk');
        Route::get('/descargar/excel/bonos/especiales/', 'SpecialBonusController@downloadBonuses')
            ->middleware('permission:report_bonusRisk');

        // TODO: Rutas de pago al personal
        Route::get('/personal/payments', 'PersonalPaymentController@getPersonalPaymentByMonth');
        Route::get('/personal/payments/year/{year}', 'PersonalPaymentController@getPersonalPaymentByYear');
        Route::get('/create/projections', 'ProjectionController@createProjections');
        Route::get('/pagos/al/personal', 'PersonalPaymentController@index')
            ->name('personal.payments.index')
            ->middleware('permission:list_personalPayments');

        // TODO: Rutas de trabajos de finanzas
        Route::get('/create/finance/works', 'FinanceWorkController@createFinanceWorks')
            ->middleware('permission:list_financeWorks');
        Route::get('/get/finance/works', 'FinanceWorkController@getFinanceWorks')
            ->middleware('permission:list_financeWorks');
        /*Route::get('/trabajos/finanzas', 'FinanceWorkController@index')
            ->name('finance.works.index')
            ->middleware('permission:list_financeWorks');*/
        Route::get('/get/info/trabajo/finance/work/{financeWork_id}', 'FinanceWorkController@getInfoTrabajoFinanceWork')
            ->middleware('permission:update_financeWorks');
        Route::post('finance/work/edit/trabajo', 'FinanceWorkController@financeWorkEditTrabajo')
            ->name('finance.work.edit.trabajo')
            ->middleware('permission:update_financeWorks');
        Route::get('/get/info/facturacion/finance/work/{financeWork_id}', 'FinanceWorkController@getInfoFacturacionFinanceWork')
            ->middleware('permission:update_financeWorks');
        Route::post('finance/work/edit/facturacion', 'FinanceWorkController@financeWorkEditFacturacion')
            ->name('finance.work.edit.facturacion')
            ->middleware('permission:update_financeWorks');

        Route::get('/get/finance/works/v2/{numberPage}', 'FinanceWorkController@getDataFinanceWorks')
            ->middleware('permission:list_financeWorks');
        Route::get('/trabajos/finanzas/v2', 'FinanceWorkController@indexV2')
            ->name('finance.works.index')
            ->middleware('permission:list_financeWorks');
        Route::get('exportar/reporte/ingresos/clientes/', 'FinanceWorkController@exportFinanceWorks')
            ->middleware('permission:list_financeWorks');

        // TODO: Rutas de Egresos Proveedores
        Route::get('/get/expenses/supplier/v2/{numberPage}', 'ExpenseSupplierController@getDataExpenseSuppliers')
            ->middleware('permission:list_expenseSupplier');
        Route::get('/egresos/proveedores/v2', 'ExpenseSupplierController@indexV2')
            ->name('expenses.supplier.index')
            ->middleware('permission:list_expenseSupplier');
        Route::get('exportar/reporte/egresos/proveedores/', 'ExpenseSupplierController@exportExpenseSuppliers')
            ->middleware('permission:export_expenseSupplier');
        Route::get('/get/info/facturacion/expense/supplier/{invoice_id}/{type}', 'ExpenseSupplierController@getInfoFacturacionExpenseSupplier')
            ->middleware('permission:modify_expenseSupplier');
        Route::post('expense/supplier/edit/facturacion', 'ExpenseSupplierController@expenseSupplierEditFacturacion')
            ->name('expense.supplier.edit.facturacion')
            ->middleware('permission:modify_expenseSupplier');

        // TODO: Rutas de Worker Accounts
        Route::get('/registrar/cuentas/trabajador/{worker_id}', 'WorkerAccountController@index')
            ->name('worker.accounts.index')
            ->middleware('permission:list_workerAccount');
        Route::get('/get/worker/accounts/{worker_id}', 'WorkerAccountController@getWorkerAccounts')
            ->middleware('permission:list_workerAccount');
        Route::post('worker/account/store/{worker_id}', 'WorkerAccountController@store')
            ->name('worker.account.store')
            ->middleware('permission:create_workerAccount');
        Route::post('worker/account/update/{account_id}', 'WorkerAccountController@update')
            ->name('worker.account.update')
            ->middleware('permission:edit_workerAccount');
        Route::post('worker/account/destroy/{account_id}', 'WorkerAccountController@destroy')
            ->name('worker.account.destroy')
            ->middleware('permission:destroy_workerAccount');

        // TODO: Rutas de Supplier Accounts
        Route::get('/registrar/cuentas/proveedores/{supplier_id}', 'SupplierAccountController@index')
            ->name('supplier.accounts.index')
            ->middleware('permission:list_supplierAccount');
        Route::get('/get/supplier/accounts/{worker_id}', 'SupplierAccountController@getWorkerAccounts')
            ->middleware('permission:list_supplierAccount');
        Route::post('supplier/account/store/{worker_id}', 'SupplierAccountController@store')
            ->name('supplier.account.store')
            ->middleware('permission:create_supplierAccount');
        Route::post('supplier/account/update/{account_id}', 'SupplierAccountController@update')
            ->name('supplier.account.update')
            ->middleware('permission:edit_supplierAccount');
        Route::post('supplier/account/destroy/{account_id}', 'SupplierAccountController@destroy')
            ->name('supplier.account.destroy')
            ->middleware('permission:destroy_supplierAccount');
        Route::get('/download/supplierexcel', 'SupplierController@generateReport')
            ->middleware('permission:exportreport_supplier');



        // TODO: Rutas de CategoryEquipments
        Route::get('/categorias/equipos/', 'CategoryEquipmentController@index')
            ->name('categoryEquipment.index')
            ->middleware('permission:listCategory_defaultEquipment');
        Route::get('/categorias/editar/{categoryEquipment}', 'CategoryEquipmentController@edit')
            ->name('categoryEquipment.edit')
            ->middleware('permission:editCategory_defaultEquipment');
        Route::post('/categorias/actualizar/{categoryEquipment}', 'CategoryEquipmentController@update')
            ->name('categoryEquipment.update')
            ->middleware('permission:editCategory_defaultEquipment');
        Route::delete('/categorias/equiposxeliminar/{id}', 'CategoryEquipmentController@destroy')
            ->name('categoryEquipment.destroy')
            ->middleware('permission:destroyCategory_defaultEquipment');
        Route::post('/categorias/equiposxrestaurar/{id}', 'CategoryEquipmentController@restore')
            ->name('categoryEquipment.restore')
            ->middleware('permission:restoreCategory_defaultEquipment');
        Route::get('/categorias/equiposeliminados/', 'CategoryEquipmentController@eliminated')
            ->name('categoryEquipment.eliminated')
            ->middleware('permission:eliminatedCategory_defaultEquipment');
        Route::get('/get/data/category/equipments/{numberPage}', 'CategoryEquipmentController@getDataCategoryEquipment');
        Route::get('/get/data/category/equipmentseliminated/{numberPage}', 'CategoryEquipmentController@getDataCategoryEquipmentEliminated');
        Route::post('/category/equipment/store', 'CategoryEquipmentController@store')
            ->name('categoryEquipment.store')
            ->middleware('permission:createCategory_defaultEquipment');
        Route::get('/get/category/equipment/typeahead', 'CategoryEquipmentController@getCategoriesTypeahead');


        // TODO: Rutas de DefaultEquipments
        Route::get('/equipos/categoria/{category_id}', 'DefaultEquipmentController@index')
            ->name('defaultEquipment.index')
            ->middleware('permission:list_defaultEquipment');
        Route::get('/equipos/categoria/{category_id}/crear', 'DefaultEquipmentController@create')
            ->name('defaultEquipment.create')
            ->middleware('permission:create_defaultEquipment');
        Route::post('store/defaultEquipment', 'DefaultEquipmentController@store')
            ->name('defaultEquipment.store')
            ->middleware('permission:create_defaultEquipment');
        Route::get('/get/data/defaultEquipments/{numberPage}', 'DefaultEquipmentController@getDataDefaultEquipments');
        Route::get('/editar/equipo/categoria/{equipment_id}', 'DefaultEquipmentController@edit')
            ->name('defaultEquipment.edit')
            ->middleware('permission:update_defaultEquipment');
        Route::post('update/defaultEquipment/{equipment_id}', 'DefaultEquipmentController@update')
            ->name('defaultEquipment.update')
            ->middleware('permission:update_defaultEquipment');
        Route::post('destroy/defaultEquipment/{equipment_id}', 'DefaultEquipmentController@destroy')
            ->name('defaultEquipment.destroy')
            ->middleware('permission:destroy_defaultEquipment');

        // TODO: Rutas de Pre Cotizaciones
        Route::get('/pre/cotizaciones/', 'ProformaController@index')
            ->name('proforma.index')
            ->middleware('permission:list_proforma');
        Route::get('/get/data/proformas/{numberPage}', 'ProformaController@getDataProformas');
        Route::get('/crear/pre/cotizacion/', 'ProformaController@create')
            ->name('proforma.create')
            ->middleware('permission:create_proforma');
        Route::get('get/data/equipments/proforma/', 'ProformaController@getDataEquipments');
        Route::get('get/data/default/equipment/{equipment_id}', 'ProformaController@getDataEquipmentDefault');
        Route::post('store/proforma', 'ProformaController@store')
            ->name('proforma.store')
            ->middleware('permission:create_proforma');
        Route::post('/destroy/proforma/{proforma_id}', 'ProformaController@destroy')
            ->name('proforma.destroy')
            ->middleware('permission:destroy_proforma');
        Route::get('ver/pre/cotizacion/{proforma_id}', 'ProformaController@show')
            ->name('proforma.show')
            ->middleware('permission:show_proforma');
        Route::get('imprimir/proforma/cliente/{quote}', 'ProformaController@printProformaToCustomer')
            ->middleware('permission:print_proforma');
        Route::post('/visto/bueno/proforma/{proforma_id}', 'ProformaController@vistoBuenoProforma')
            ->middleware('permission:confirm_proforma');
        Route::get('editar/pre/cotizacion/{proforma_id}', 'ProformaController@edit')
            ->name('proforma.edit')
            ->middleware('permission:update_proforma');
        Route::post('update/proforma', 'ProformaController@update')
            ->name('proforma.update')
            ->middleware('permission:update_proforma');
        Route::get('add/data/default/equipment/proforma/{proforma_id}/{equipment_id}', 'ProformaController@addDataDefaultEquipmentProforma');
        Route::post('destroy/equipment/proforma/{proforma_id}/{equipment_id}', 'ProformaController@destroyEquipmentProforma');
        Route::post('/update/percentages/equipment/{id_equipment}/proforma/{id_proforma}', 'ProformaController@changePercentagesEquipment')
            ->middleware('permission:changePercentage_proforma');
        Route::get('/editar/equipo/pre/cotizacion/{equipment_id}', 'ProformaController@editEquipmentProforma')
            ->name('equipment.proforma.edit')
            ->middleware('permission:editEquipment_proforma');
        Route::post('update/equipment/proforma/{equipment_id}', 'ProformaController@updateEquipmentProforma')
            ->name('equipment.proforma.update')
            ->middleware('permission:destroyEquipment_proforma');

        Route::get('get/contracts/for/expire/', 'ContractController@getContractsForExpire');

        // TODO: Descargar por anaqueles
        Route::get('/download/excel/materials/anaquel/', 'ShelfController@exportMaterialsAnaquel');

        // TODO: Rutas de Reporte de Solicitudes por cotizacion
        Route::get('/get/outputs/by/quote/v2/{numberPage}', 'OutputController@getOutputsByQuote');
        Route::get('/reporte/de/sikicitudes/por/cotizacion/', 'OutputController@reportOutputsByQuote')
            ->name('report.outputs.by.quote')
            ->middleware('permission:report_output');
        Route::get('/exportar/reporte/ordenes/by/quote/v2/', 'OutputController@exportReportOutputsByQuote');

        // TODO: Resumen de cotizaciones
        Route::get('/resumen/de/cotizaciones/', 'QuoteController@resumenQuote')
            ->name('resumen.quote')
            ->middleware('permission:resumen_quote');
        Route::get('/get/resumen/quote/', 'QuoteController@getResumenQuote');
        Route::get('/get/info/resumen/quote/{quote_id}', 'QuoteController@getInfoResumenQuote');
        Route::get('/exportar/pdf/materiales/cotizaciones/v2/', 'QuoteController@exportPDFMaterialesCotizaciones');


        // TODO: Rutas de Reporte de Materiales en Ordenes de Compra
        Route::get('/get/data/order/purchase/by/material/{numberPage}', 'OrderPurchaseController@getReportOrderPurchaseByMaterial');
        Route::get('/reporte/de/ordenes/compra/por/materiales', 'OrderPurchaseController@reportOrderPurchaseByMaterial')
            ->name('report.orders.by.materials')
            ->middleware('permission:report_orderPurchaseExpress');
        Route::get('/exportar/reporte/ordenes/by/material/v2/', 'OrderPurchaseController@exportReportOrdersByMaterial');

        // Ruta de prueba para ver las ubicaciones de un material
        Route::get('/ver/ubicacion/material/{material}', 'ReportController@getLocationsGeneralMaterial');

        // TODO: Rutas de Inventario Fisico
        Route::get('/get/data/inventory/{numberPage}', 'InventoryController@getDataInventory');
        Route::get('/listado/de/inventario/fisico/', 'InventoryController@listInventory')
            ->name('inventory.index')
            ->middleware('permission:list_inventory');
        Route::get('/exportar/listado/inventario/v2/', 'InventoryController@exportListInventory')
            ->middleware('permission:export_inventory');
        Route::post('/save/data/inventory/{id}', 'InventoryController@saveListInventory')
            ->name('inventory.save')
            ->middleware('permission:save_inventory');

        Route::get('/exportar/entradas/almacen/v2/', 'EntryController@exportEntriesAlmacen');

        // TODO: Rutas Rotacion de materiales
        Route::get('/store/rotation/material/', 'RotationMaterialController@storeRotationMaterial');
        Route::get('/get/rotation/material/', 'RotationMaterialController@getRotationMaterial');
        Route::get('/get/data/rotations/v2/{page}', 'RotationMaterialController@getDataRotations');

        // TODO: Rutas de generacion de tipos de cambio
        Route::get('/generar/tipo/cambios/', 'TipoCambioController@generarTipoCambios');
        Route::get('/guardar/tipo/cambios/', 'TipoCambioController@guardarTipoCambios');
        Route::get('/rellenar/tipo/cambios/', 'TipoCambioController@rellenarTipoCambios');
        Route::get('/obtener/tipo/cambio/', 'TipoCambioController@obtenerTipoCambio');
        Route::get('/mostrar/tipo/cambio/bd/actual/', 'TipoCambioController@mostrarTipoCambioActual');
        Route::get('/mostrar/tipo/cambio/bd/prueba/', 'TipoCambioController@mostrarTipoCambioPrueba');

        // TODO: Rutas de subida de archivos de materiales
        Route::get('/importar/archivos/stocks/materiales', 'UploadFilesController@showUploadFilesStocksMaterials')
            ->name('stocks.files.index')
            ->middleware('permission:stock_files');
        Route::post('/upload/files/stocks/min/max/materials', 'UploadFilesController@uploadFilesStocksMaterials')
            ->name('stocks.files.store')
            ->middleware('permission:stock_files');
        Route::get('/download/example/stock/file', 'UploadFilesController@downloadExampleStockFile');

        // TODO: Rutas de Guias de Remision
        Route::get('/guias/de/remision/', 'ReferralGuideController@index')
            ->name('referral.guide.index')
            ->middleware('permission:list_referralGuide');
        Route::get('/get/data/referral/guides/{numberPage}', 'ReferralGuideController@getDataGuides');
        Route::get('/crear/guia/de/remision/', 'ReferralGuideController@create')
            ->name('referral.guide.create')
            ->middleware('permission:create_referralGuide');
        Route::post('store/guide/referral', 'ReferralGuideController@store')
            ->name('referral.guide.store')
            ->middleware('permission:create_referralGuide');
        Route::post('/destroy/guide/referral/{guide_id}', 'ReferralGuideController@destroy')
            ->name('referral.guide.destroy')
            ->middleware('permission:destroy_referralGuide');
        Route::get('ver/guia/remision/{guide_id}', 'ReferralGuideController@show')
            ->name('referral.guide.show')
            ->middleware('permission:list_referralGuide');
        Route::get('imprimir/guia/remision/{guide_id}', 'ReferralGuideController@printReferralGuide')
            ->middleware('permission:print_referralGuide');
        Route::get('editar/guia/de/remision/{guide_id}', 'ReferralGuideController@edit')
            ->name('referral.guide.edit')
            ->middleware('permission:edit_referralGuide');
        Route::post('update/guide/referral/{id}', 'ReferralGuideController@update')
            ->name('referral.guide.update')
            ->middleware('permission:edit_referralGuide');
        Route::get('/exportar/guias/remision/v2/', 'ReferralGuideController@exportReferralGuides')
            ->middleware('permission:download_referralGuide');

        // TODO: Rutas Punto de Venta
        Route::get('/crear/venta/V1', 'PuntoVentaController@index')
            ->name('puntoVenta.index');
        Route::get('/crear/venta/V2', 'PuntoVentaController@indexV2')
            ->name('puntoVenta.indexV2');
        Route::get('/get/data/products/{page}', 'PuntoVentaController@getDataProducts');
        Route::get('/get/data/products/v2/{page}', 'PuntoVentaController@getDataProductsV2');
        Route::get('/get/discount/product/{product_id}', 'PuntoVentaController@getDiscountProduct');
        Route::post('/store/venta/', 'PuntoVentaController@store')
            ->name('puntoVenta.store');
        Route::get('imprimir/documento/venta/{id}', 'PuntoVentaController@printDocumentSale')
            ->name('puntoVenta.print');
        Route::get('/listado/ventas/', 'PuntoVentaController@listar')
            ->name('puntoVenta.list');
        Route::get('/get/data/sales/{page}', 'PuntoVentaController@getSalesAdmin');
        Route::get('/sales/{orderId}/details', 'PuntoVentaController@getOrderDetails');
        Route::post('/anular/order/{order}', 'PuntoVentaController@anularOrder');

        Route::post('/sales/update-invoice-data', 'PuntoVentaController@updateInvoiceData');
        Route::post('/facturador/generar', 'NubefactController@generarComprobante')->name('facturador.generar');

        Route::get('/listado/ventas/error/', 'PuntoVentaController@listarErrors')
            ->name('puntoVenta.listError');
        Route::get('/get/data/sales/error/{page}', 'PuntoVentaController@getSalesErrorAdmin');
        Route::post('/sales/errors/{id}/discard', 'PuntoVentaController@discardSunatError');
        //get/data/products

        // TODO: Rutas Punto de Venta
        Route::get('/separar/paquetes/materiales', 'MaterialController@materialSeparatePack')
            ->name('material.separate.pack');
        Route::get('/get/data/material/pack/V2/{numberPage}', 'MaterialController@getDataMaterialsPack');
        Route::post('/store/separate/pack', 'MaterialController@storeSeparatePack')
            ->name('save.separate.pack');
        Route::get('/generar/combos/materiales', 'ComboController@generateComboMaterials')
            ->name('material.generate.combo');
        Route::post('/store/generate/pack', 'ComboController@storeGeneratePack')
            ->name('save.generate.pack');
        Route::get('/get/data/combos/V2/{numberPage}', 'ComboController@getDataCombos');
        Route::get('/listado/combos/materiales', 'ComboController@index')
            ->name('index.combos');
        Route::post('combo/destroy', 'ComboController@destroy')
            ->name('combo.destroy')/*
            ->middleware('permission:destroy_category')*/;
        Route::get('/get/materials/combo/{combo}', 'ComboController@getDataMaterialsCombo');

        Route::get('/ver/caja/{type}', 'CashRegisterController@indexCashRegister')
            ->name('index.cashRegister');
        Route::post('open/cashRegister', 'CashRegisterController@openCashRegister')
            ->name('open.cashRegister')/*
            ->middleware('permission:destroy_category')*/;
        Route::post('close/cashRegister', 'CashRegisterController@closeCashRegister')
            ->name('close.cashRegister')/*
            ->middleware('permission:destroy_category')*/;
        Route::post('income/cashRegister', 'CashRegisterController@incomeCashRegister')
            ->name('income.cashRegister')/*
            ->middleware('permission:destroy_category')*/;
        Route::post('expense/cashRegister', 'CashRegisterController@expenseCashRegister')
            ->name('expense.cashRegister')/*
            ->middleware('permission:destroy_category')*/;
        Route::get('/get/data/movements/V2/{numberPage}', 'CashRegisterController@getDataMovements');
        Route::post('regularize/cashRegister', 'CashRegisterController@regularizeCashRegister')
            ->name('regularize.cashRegister');

        // TODO: Promotions Seasonal
        Route::get('/get/data/promotions/seasonal/V2/{numberPage}', 'SeasonalPromotionController@getDataPromotions');
        Route::get('/listado/promocion/temporada', 'SeasonalPromotionController@index')
            ->name('promotion.seasonal.index');
        Route::get('/crear/promocion/temporada', 'SeasonalPromotionController@create')
            ->name('promotion.seasonal.create');
        Route::post('/store/promotion/seasonal/', 'SeasonalPromotionController@store')
            ->name('promotion.seasonal.store');
        Route::post('/destroy/promotion/seasonal/', 'SeasonalPromotionController@destroy')
            ->name('promotion.seasonal.destroy');

        Route::get('/get/price/list/material/{material_id}', 'MaterialController@getPriceListMaterial');
        Route::get('/get/price/percentage/material/{material_id}', 'MaterialController@getPricePercentageMaterial');

        Route::post('/set/price/directo/material', 'MaterialController@setPriceDirectoMaterial')
            ->name('material.set.price.directo');
        Route::post('/set/price/porcentaje/material', 'MaterialController@setPricePorcentajeMaterial')
            ->name('material.set.price.porcentaje');

        Route::post('/manage/price/material', 'MaterialController@managePrice')
            ->name('material.manage.price');

        // TODO: Ganancia Diaria
        Route::get('/get/data/ganancias/V2/{numberPage}', 'GananciaDiariaController@getDataGanancias');
        Route::get('/get/data/ganancia/details/V2/{numberPage}', 'GananciaDiariaController@getDataGananciaDetails');
        Route::get('/listado/ganancias/diarias', 'GananciaDiariaController@index')
            ->name('ganancia.index');
        Route::get('/listado/ganancia/detalles/{id}', 'GananciaDiariaController@indexDetail')
            ->name('ganancia.detail.index');
        Route::get('/listado/ganancia/detalles/trabajador/{sale_id}', 'GananciaDiariaController@indexDetailTrabajador')
            ->name('ganancia.detail.index');
        Route::get('/get/data/ganancias/trabajador/V2/{numberPage}', 'GananciaDiariaController@getDataGananciasTrabajador');
        Route::get('/listado/ganancias/diarias/trabajador', 'GananciaDiariaController@indexTrabajador')
            ->name('ganancia.index.trabajador');

        Route::get('/export/ganancias/trabajador', 'GananciaDiariaController@exportGananciasTrabajador')
            ->name('ganancia.export.trabajador');
        Route::get('/export/ganancia/ventas-detallado', 'GananciaDiariaController@exportGananciaVentasDetallado')
            ->name('ganancia.export.ventas_detallado');

        Route::get('/material-unpack/{id}/childs', 'MaterialUnpackController@getChilds');
        Route::delete('/material-unpack/{id}', 'MaterialUnpackController@destroy');
        Route::post('/material-unpack/store', 'MaterialUnpackController@store');
        Route::get('/material-unpack/{id}/child-materials', 'MaterialUnpackController@getChildMaterials');

        Route::get('/sales/chart-data-sale', 'GraphsController@getChartDataSale');
        Route::get('/sales/chart-data-utilidad', 'GraphsController@getChartDataCashFlow');

        // TODO: Metas
        Route::prefix('metas')->name('metas.')->group(function () {

            // Listado
            Route::get('/', [MetaController::class, 'index'])->name('index');

            // Configurar tipo de meta (POST desde el formulario de configuración)
            Route::post('/config-tipo', [MetaController::class, 'configTipo'])->name('configTipo');

            // Crear (la vista la harás luego)
            Route::get('/create', [MetaController::class, 'create'])->name('create');

            // Guardar meta
            Route::post('/store', [MetaController::class, 'store'])->name('store');

            Route::get('/{meta}/edit', [MetaController::class, 'edit'])->name('edit');
            Route::put('/{meta}/update', [MetaController::class, 'update'])->name('update');

            Route::get('/weeks', [MetaController::class, 'getWeeksByYearMonth'])->name('weeks');

            // Progreso (lo verás después)
            //Route::get('/progreso', [MetaController::class, 'progreso'])->name('progreso');

            // Eliminar meta
            Route::delete('/delete/{meta}', [MetaController::class, 'destroy'])->name('destroy');

            Route::get('/ranking', [MetaController::class, 'ranking'])->name('ranking');

            Route::get('/ranking/data', [MetaController::class, 'getRankingData'])->name('ranking.data');
        });

        // TODO: Faces
        Route::get('/faces', [\App\Http\Controllers\FaceController::class, 'index'])->name('faces.index');
        Route::post('/faces', [\App\Http\Controllers\FaceController::class, 'store'])->name('faces.store');
        Route::get('/faces/verify', [\App\Http\Controllers\FaceController::class, 'verify'])->name('faces.verify');

        // TODO: DataGeneral
        Route::get('datos/generales', 'DataGeneralController@index')->name('dataGeneral.index')
            ->middleware('permission:list_dataGeneral');
        Route::get('/get/data/dataGeneral/{numberPage}', 'DataGeneralController@getDataGeneral');
        Route::post('datos/generales/store', 'DataGeneralController@store')->name('dataGeneral.store')
            ->middleware('permission:create_dataGeneral');
        Route::post('datos/generales/update/{id}', 'DataGeneralController@update')->name('dataGeneral.update')
            ->middleware('permission:update_dataGeneral');

        Route::post('/leer/notificaciones/pop_up', 'NotificationController@readPopupNotifications');

        // TODO: QuoteSale
        Route::get('cotizaciones/venta', 'QuoteSaleController@index')
            ->name('quoteSale.index')
            ->middleware('permission:list_quoteSale');
        Route::get('/get/data/quotes/sale/index/v2/{numberPage}', 'QuoteSaleController@getDataQuotesIndex')
            ->middleware('permission:list_quoteSale');

        Route::get('cotizaciones/venta/facturadas', 'QuoteSaleController@indexFacturadas')
            ->name('quoteSale.index.facturadas')
            ->middleware('permission:list_quoteSale');
        Route::get('/get/data/quotes/sale/facturadas/index/v2/{numberPage}', 'QuoteSaleController@getDataQuotesSalesIndex')
            ->middleware('permission:list_quoteSale');

        Route::get('cotizaciones/venta/totales', 'QuoteSaleController@indexGeneral')
            ->name('quoteSale.list.general')
            ->middleware('permission:list_quoteSale');
        Route::get('crear/cotizacion/venta', 'QuoteSaleController@create')
            ->name('quoteSale.create')
            ->middleware('permission:create_quoteSale');
        Route::get('/get/quote/sale/materials/totals', 'QuoteSaleController@getMaterialTotals')
            ->middleware('permission:create_quoteSale');
        Route::get('/get/quote/sale/materials', 'QuoteSaleController@getMaterials')
            ->middleware('permission:create_quoteSale');
        Route::post('store/quote/sale', 'QuoteSaleController@store')
            ->name('quoteSale.store')
            ->middleware('permission:create_quoteSale');
        Route::get('imprimir/cotizacion/cliente/{quote}', 'QuoteSaleController@printQuoteToCustomer')
            ->middleware('permission:printCustomer_quote');

        Route::get('editar/cotizacion/venta/{quote}', 'QuoteSaleController@edit')
            ->name('quoteSale.edit')
            ->middleware('permission:edit_quoteSale');
        Route::post('update/quote/sale', 'QuoteSaleController@update')
            ->name('quoteSale.update')
            ->middleware('permission:edit_quoteSale');
        Route::post('/destroy/quote/sale/{quote}', 'QuoteSaleController@destroy')
            ->name('quoteSale.destroy')
            ->middleware('permission:destroy_quoteSale');
        Route::post('/update/equipment/{id_equipment}/quote/sale/{id_quote}', 'QuoteSaleController@updateEquipmentOfQuote')
            ->name('quoteSale.update.equipment')
            ->middleware('permission:edit_quoteSale');

        Route::get('registrar/comprobante/venta/{type}', 'QuoteSaleController@showRegistrarComprobante')
            ->name('show.register.comprobante')
            ->middleware('permission:edit_quoteSale');
        Route::get('/quotes/buscar', 'QuoteSaleController@buscar')->name('quotes.buscar');
        Route::get('/get/data/quotes/sale/{id}', 'QuoteSaleController@getDataIndividual')->name('quotes.getDataIndividual');
        Route::post('/store/sale/from/quote', 'QuoteSaleController@storeFromQuote')
            ->middleware('permission:edit_quoteSale');

        Route::post('/quotes/update-general', 'QuoteSaleController@updateDatosGeneral')
            ->name('quotes.sales.update.general');

        Route::post('/raise/quote/sale/{quote}', 'QuoteSaleController@raiseQuote')
            ->name('quote.raise.quote.sale')
            ->middleware('permission:raise_quote');

        Route::get('ver/cotizacion/venta/{quote}', 'QuoteSaleController@show')
            ->name('quoteSale.show')
            ->middleware('permission:list_quoteSale');

        Route::get('/quotes/stock-items/{stockItemId}/available-items', 'QuoteSaleController@getAvailableItemsByStockItem')
            ->name('quotes.stock-items.available-items');

        // TODO: PromotionLimits
        Route::get('promociones/por/limite', 'PromotionLimitController@index')
            ->name('promotionLimit.index')
            ->middleware('permission:list_promotionLimit');
        Route::get('crear/promocion/por/limite', 'PromotionLimitController@create')
            ->name('promotionLimit.create')
            ->middleware('permission:create_promotionLimit');
        Route::get('/get/promotion/limits/materials/totals', 'PromotionLimitController@getMaterialTotals')
            ->middleware('permission:create_promotionLimit');
        Route::get('/get/promotion/limit/materials', 'PromotionLimitController@getMaterials')
            ->middleware('permission:create_promotionLimit');
        Route::post('store/promotion/limit', 'PromotionLimitController@store')
            ->name('promotionLimit.store')
            ->middleware('permission:create_promotionLimit');
        Route::get('/get/data/promotion/limits/{numberPage}', 'PromotionLimitController@getDataPromotions');

        Route::post('/destroy/promotion/limits/{promotion_id}', 'PromotionLimitController@destroy')
            ->name('promotionLimit.destroy')
            ->middleware('permission:destroy_promotionLimit');

        Route::get('editar/promocion/limite/{promotion}', 'PromotionLimitController@edit')
            ->name('promotionLimit.edit')
            ->middleware('permission:edit_promotionLimit');
        Route::post('update/promotion/limit', 'PromotionLimitController@update')
            ->name('promotionLimit.update')
            ->middleware('permission:edit_promotionLimit');

        Route::get('orden/de/promociones/', 'PromotionOrderController@index')
            ->name('promotionOrder.index')
            ->middleware('permission:list_promotionLimit');
        Route::post('/promotion-orders/update-order', 'PromotionOrderController@updateOrder');
        Route::get('/get/data/promotion/orders/{numberPage}', 'PromotionOrderController@getDataPromotions');

        Route::post('/check-promotions', 'PromotionOrderController@checkPromotions');

        // TODO: KARDEX
        Route::get('/kardex/{materialId}', 'InventoryMovementController@kardex')
            ->name('kardex.material');
        Route::get('/kardex', 'InventoryMovementController@index')
            ->name('kardex.index');
        Route::get('/materials/select', 'MaterialController@selectAjax')->name('materials.selectAjax');
        Route::get('/stock-items/select', 'MaterialController@selectStockItemsAjax')
            ->name('stockitems.select');
        Route::get('/warehouses/select', 'MaterialController@selectWarehousesAjax')
            ->name('warehouses.select');

        // TODO: PARAMTRIZACION DE MATERIAL DETAILS
        Route::prefix('settings')
            ->group(function () {
                Route::get(
                    'material-details',
                    [MaterialDetailSettingController::class, 'index']
                )->name('settings.material-details.index');

                Route::post(
                    'material-details',
                    [MaterialDetailSettingController::class, 'store']
                )->name('settings.material-details.store');

            });

        Route::prefix('materials-presentations/')->group(function () {
            Route::get('material/{material}/presentations', [MaterialPresentationController::class, 'index'])
                ->name('material-presentations.index');

            Route::post('material/{material}/presentations', [MaterialPresentationController::class, 'store'])
                ->name('material-presentations.store');

            Route::put('presentation/{presentation}', [MaterialPresentationController::class, 'update'])
                ->name('material-presentations.update');

            Route::delete('presentation/{presentation}', [MaterialPresentationController::class, 'destroy'])
                ->name('material-presentations.destroy');

            Route::patch('presentation/{presentation}/toggle', [MaterialPresentationController::class, 'toggle'])
                ->name('material-presentations.toggle');
        });

        // Cash Boxes
        Route::prefix('cash-boxes')
            ->group(function () {
                Route::get('/', 'CashBoxController@index')->name('cashBox.index');
                Route::get('/list', 'CashBoxController@listar')->name('cashBox.list');
                Route::post('/store', 'CashBoxController@store')->name('cashBox.store');
                Route::post('/update', 'CashBoxController@update')->name('cashBox.update');
                Route::post('/toggle', 'CashBoxController@toggle')->name('cashBox.toggle');

            });

        // Subtypes
        Route::prefix('cash-box-subtypes')
            ->group(function () {
                Route::get('/', 'CashBoxSubtypeController@index')->name('cashBoxSubtype.index');
                Route::get('/list', 'CashBoxSubtypeController@listar')->name('cashBoxSubtype.list');
                Route::post('/store', 'CashBoxSubtypeController@store')->name('cashBoxSubtype.store');
                Route::post('/update', 'CashBoxSubtypeController@update')->name('cashBoxSubtype.update');
                Route::post('/toggle', 'CashBoxSubtypeController@toggle')->name('cashBoxSubtype.toggle');

            });

        Route::prefix('/cash-movements')->group(function () {

            // Vista usuario
            Route::get('/my', 'CashBoxController@myIndex')->name('cashMovement.my.index');
            Route::get('/my/list', 'CashBoxController@myList')->name('cashMovement.my.list');

            // Vista admin
            Route::get('/admin', 'CashBoxController@adminIndex')->name('cashMovement.admin.index');
            Route::get('/admin/list', 'CashBoxController@adminList')->name('cashMovement.admin.list');

        });

        Route::prefix('cash-registers')->group(function () {
            Route::post('/open', 'CashRegisterController@open')->name('cashRegister.open');
            Route::get('/session/{cashBox}', 'CashRegisterController@session')->name('cashRegister.session');
            Route::post('/arqueo/', 'CashRegisterController@arqueo')->name('cashRegister.arqueo');
            Route::get('/{cashRegister}/movements/{pageNumber?}', 'CashRegisterController@getSessionMovements')
                ->name('cashRegister.movements');
            Route::post('/income', 'CashRegisterController@income')->name('cashRegister.income');
            Route::post('/expense', 'CashRegisterController@expense')->name('cashRegister.expense');

            Route::post('/{cashRegister}/arqueo', 'CashRegisterController@arqueo')->name('cashRegister.arqueo');
            Route::post('/regularize', 'CashRegisterController@regularize')->name('cashMovement.regularize');
        });

        Route::get('/financial/summary', 'CashRegisterController@financialSummary')->name('financial.summary');
        Route::get('/sales/export-range/excel', 'GraphsController@exportSalesByDateRange')->name('sales.export.range');
        Route::get('/cashflow/export-range/excel', 'GraphsController@exportCashFlowRangeExcel')
            ->name('cashflow.export.range');

        Route::prefix('shipping-guides')->group(function () {
            // Vistas
            Route::get('/listado', 'ShippingGuideController@viewIndex')->name('shipping_guides.view');
            Route::get('/create', 'ShippingGuideController@create')->name('shipping_guides.create');

            // Ajax / API internas
            Route::get('/list', 'ShippingGuideController@index')->name('shipping_guides.list');
            Route::post('/', 'ShippingGuideController@store')->name('shipping_guides.store');

            Route::get('/detalles/{guide}', 'ShippingGuideController@show')->name('shipping_guides.show');
            Route::post('/{guide}/consult', 'ShippingGuideController@consultNubefact')->name('shipping_guides.consult');
            Route::post('/export', 'ShippingGuideController@exportExcel')->name('shipping_guides.export');
            //Route::post('/{id}/consult', 'ShippingGuideController@consultNubefact')->name('shipping_guides.consult');
        });

        Route::get('/customers/{customer}/payload', 'CustomerController@payload')->name('customers.payload');
        Route::get('shipping-guides/sale-items', 'ShippingGuideController@saleItems')
            ->name('shipping_guides.sale_items');
        Route::get('/listado/sales/select2/', 'ShippingGuideController@select2')->name('sales.select2');
        Route::get('/sales/{sale}/items', 'ShippingGuideController@items')->name('sales.items');

        Route::get('ubigeo/departments/select2', 'UbigeoController@departmentsSelect2')->name('ubigeo.departments.select2');
        Route::get('ubigeo/provinces/select2', 'UbigeoController@provincesSelect2')->name('ubigeo.provinces.select2');
        Route::get('ubigeo/districts/select2', 'UbigeoController@districtsSelect2')->name('ubigeo.districts.select2');

        Route::get('/materials/select2', 'MaterialController@select2')->name('materials.select2');

        Route::get('/stock-items/list', 'StockItemController@list')->name('stockitems.list');
        Route::get('/stock-items', 'StockItemController@index')->name('stockitems.index');
        Route::post('/stock-items/{id}/toggle-inventory', 'StockItemController@toggleInventory');
        Route::post('/stock-items/{id}/toggle-active', 'StockItemController@toggleActive');
        Route::get('/view/material/variants/{id}', 'StockItemController@viewMaterialVariants')->name('stockitems.view.variants');
        Route::get('/view/material/all/variants/{id}', 'StockItemController@getItemsMaterial')->name('stockitems.getItemsMaterial');

        Route::get('/dashboard/stock-items/{stockItem}/inventory-levels', 'StockItemController@getInventoryLevels')
            ->name('stockitems.inventory-levels');

        Route::get('/dashboard/material/{material}/inventory-levels', 'StockItemController@getInventoryLevelsByMaterial')
            ->name('material.inventory-levels');

        Route::post('/dashboard/stock-items/{stockItem}/inventory-levels', 'StockItemController@updateInventoryLevels')
            ->name('stockitems.inventory-levels.update');

        //COLORS
        /*Route::get('/all/tallas', 'TallaController@getTallas')
            ->middleware('permission:list_unitMeasure')*/;
        /*Route::get('tallas', 'TallaController@index')
            ->name('talla.index')
            ->middleware('permission:list_unitMeasure')*/;
        /*Route::get('crear/talla', 'TallaController@create')
            ->name('talla.create')
            ->middleware('permission:list_unitMeasure')*/;
        Route::post('color/store', 'ColorController@store')
            ->name('color.store')
            /*->middleware('permission:list_unitMeasure')*/;
        /*Route::get('/editar/talla/{id}', 'TallaController@edit')
            ->name('talla.edit')
            ->middleware('permission:list_unitMeasure')*/;
        /*Route::post('talla/update', 'TallaController@update')
            ->name('talla.update')
            ->middleware('permission:list_unitMeasure')*/;
        /*Route::post('talla/destroy', 'TallaController@destroy')
            ->name('talla.destroy')
            ->middleware('permission:list_unitMeasure')*/;
        /*Route::post('/talla/delete-multiple', 'TallaController@deleteMultiple');*/

        Route::get('/materials/stock-items-entry/json', 'MaterialController@getJsonMaterialStockItemsEntryV2')
            ->name('materials.stock-items-entry.json');
        Route::get('/materials/entry/search', 'MaterialController@getJsonMaterialsForEntry')
            ->name('materials.entry.search');

        Route::get('/materials/order/purchase/search', 'MaterialController@getJsonMaterialsForOrderPurchase')
            ->name('materials.order.purchase.search');

        Route::get('/materials/{material}/stock-items-entry', 'MaterialController@getStockItemsForEntry')
            ->name('materials.stock-items.entry');

        Route::get('/get/material/{material}/stock-items', 'PuntoVentaController@getStockItemsByMaterial')->name('get.stock-items.by.material');

        Route::get('/customer/search-or-create/{documento}', 'CustomerController@searchOrCreateByDocument')
            ->name('customer.searchOrCreateByDocument');

        Route::get('/customer/decolecta/{documento}', 'CustomerController@consultarDocumentoDecolecta')
            ->name('customer.consultarDocumentoDecolecta');


        Route::get('/sale/partial-payment/{sale_id}', 'SalePartialPaymentController@getBySale');
        Route::post('/sale/partial-payment/store', 'SalePartialPaymentController@store');
        Route::delete('/sale/partial-payment/{id}', 'SalePartialPaymentController@destroy');
        Route::post('/sale/generate-invoice', 'SalePartialPaymentController@generateInvoiceFromSale');
        Route::post('/sale/update-dispatch-status', 'SalePartialPaymentController@updateDispatchStatus');

        Route::post('/consultar/anulacion/{id}', 'PuntoVentaController@consultarAnulacion');

        Route::post('/generar/nota-credito/total/{id}', 'PuntoVentaController@generarNotaCreditoTotal');
        Route::post('/consultar/nota-credito/{id}', 'PuntoVentaController@consultarNotaCredito');

        Route::get('/nota-credito/parcial/data/{id}', 'PuntoVentaController@getCreditNotePartialData');
        Route::post('/generar/nota-credito/parcial/{id}', 'PuntoVentaController@generarNotaCreditoParcial');

        Route::post('/fix/stock-item/output-adjustment', 'InventoryMigrationController@adjustStockItemStockOut')
            ->name('stock-item.output-adjustment');

        Route::get('configuracion/usuarios/web', 'ConfigUserWebController@listar')
                    ->name('configUserWeb.index')
                    ->middleware('permission:listUser_configUserWeb');
        Route::get('configuracion/usuarios/web/listado', 'ConfigUserWebController@getUsers')
            ->name('configUserWeb.getUsers')
            ->middleware('permission:listUser_configUserWeb');
        Route::get('configuracion/usuarios/web/{id}/editar', 'ConfigUserWebController@edit')
            ->name('configUserWeb.edit')
            ->middleware('permission:editUser_configUserWeb');

        Route::post('configuracion/usuarios/web/{id}/actualizar', 'ConfigUserWebController@update')
            ->name('configUserWeb.update')
            ->middleware('permission:editUser_configUserWeb');

        Route::post('configuracion/usuarios/web/{id}/resetear-password', 'ConfigUserWebController@resetPassword')
            ->name('configUserWeb.resetPassword')
            ->middleware('permission:resetPasswordUser_configUserWeb');

        Route::post('configuracion/usuarios/web/{id}/cambiar-estado', 'ConfigUserWebController@changeStatus')
            ->name('configUserWeb.changeStatus')
            ->middleware('permission:changeStatusUser_configUserWeb');

        Route::prefix('/ventas-libres')->group(function () {
            Route::get('/', [FreeSaleController::class, 'index'])
                ->name('freeSale.index');

            Route::get('/crear', [FreeSaleController::class, 'create'])
                ->name('freeSale.create');

            Route::post('/', [FreeSaleController::class, 'store'])
                ->name('freeSale.store');

            Route::post('/{sale}/anular', [FreeSaleController::class, 'annul'])
                ->name('freeSale.annul');
        });
    });
});

Route::prefix('store-web/')->group(function () {
    Route::get('inicio/', [StoreWebController::class, 'home'])
        ->name('store-web.home');
    Route::get('catalogo/', [StoreWebController::class, 'catalog'])
        ->name('store-web.catalog');
    Route::get('tienda/', [StoreWebController::class, 'tienda'])
        ->name('store-web.tienda');
    Route::get('products/data/{pageNumber?}', [StoreWebController::class, 'getDataProductsV2'])
        ->name('shop.products.data');
    Route::get('categories/data', [StoreWebController::class, 'getCategoriesData'])
        ->name('shop.categories.data');
    Route::get('sizes/data', [StoreWebController::class, 'getSizesData'])
        ->name('shop.sizes.data');
    Route::get('colors/data', [StoreWebController::class, 'getColorsData'])
        ->name('shop.colors.data');
    Route::get('product/{material}', [StoreWebController::class, 'showProduct'])
        ->name('shop.product.show');
    Route::get('producto/{material}', [StoreWebController::class, 'showProduct'])
        ->name('shop.product.show.not.price');

    Route::get('search-product', [StoreWebController::class, 'searchProduct'])
        ->name('shop.product.search');


});

Route::get('/test-ruc/{ruc}', 'CustomerController@testRuc');
Route::get('/test-dni/{dni}', 'CustomerController@testDni');

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/get/type/exchange', 'FinanceWorkController@getTypeExchange');

Route::get('/api/sunat/', 'TipoCambioController@mostrarTipoCambioActual');

Route::get('/api/sunat/v2', function () {
    $token = 'apis-token-1.aTSI1U7KEuT-6bbbCguH-4Y8TI6KS73N';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.apis.net.pe/v1/tipo-cambio-sunat?',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Referer: https://apis.net.pe/tipo-de-cambio-sunat-api',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;

});

Route::get('/api/sunat/v1', function () {
    // Datos
    //$token = 'apis-token-8477.FTHJ05yz-JvXpWy3T6ynfT7CVd9sNOTK';
    $token = env('TOKEN_DOLLAR');
    $fecha = \Carbon\Carbon::now('America/Lima');
    $fechaFormateada = $fecha->format('Y-m-d');

// Iniciar llamada a API
    $curl = curl_init();

    curl_setopt_array($curl, array(
        // para usar la api versión 2
        CURLOPT_URL => 'https://api.apis.net.pe/v2/sbs/tipo-cambio?date=' . $fechaFormateada,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Referer: https://apis.net.pe/api-tipo-cambio-sbs.html',
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    if ($response === false) {
        // Error en la ejecución de cURL
        $errorNo = curl_errno($curl);
        $errorMsg = curl_error($curl);
        curl_close($curl);

        // Manejar el error
        //echo "cURL Error #{$errorNo}: {$errorMsg}";
    } else {
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode >= 200 && $httpCode < 300) {
            // La solicitud fue exitosa
            $data = json_decode($response, true);
            // Manejar la respuesta exitosa
            //echo "Respuesta exitosa: ";
            //print_r($data);
        } else {
            // La solicitud no fue exitosa
            // Decodificar el mensaje de error si la respuesta está en formato JSON
            $errorData = json_decode($response, true);
            //echo "Error en la solicitud: ";
            ///print_r($errorData);
            $response = [
                "precioCompra"=> 3.738,
                "precioVenta"=> 3.746,
                "moneda"=> "USD",
                "fecha"=> "2024-05-24"
            ];
        }
    }

    //curl_close($curl);
// Datos listos para usar

    //var_dump($tipoCambioSbs);
    //$responseObject = json_decode(json_encode($response));

    return $response;

});

Route::get('/test/view/inventory', 'InventoryMovementController@test');

Route::get('/inventory-migration/run', 'InventoryMigrationController@run')
    ->name('inventory.migration.run');
Route::get('/fix-quotes-stock-items-simple', 'InventoryMigrationController@fixQuotesStockItemsSimple');
Route::get('/fix/material-201/output-adjustment', 'InventoryMigrationController@adjustMaterial201Output');
Route::get('/fix/material/output-adjustment/{material_id}/{quantity}', 'InventoryMigrationController@adjustMaterialStockOut');

Route::get('/internal/adjust-entry-cost', 'InventoryMigrationController@adjustEntryCost')
    ->middleware(['auth', 'check.user.enabled']);