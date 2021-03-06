<?php

namespace Raffles\Modules\Poga\Models;

use Raffles\Models\{ Document, FeaturedPhoto, UnfeaturedPhoto };
use Raffles\Modules\Poga\Models\Traits\InmuebleTrait;

use Illuminate\Database\Eloquent\Model;

class Inmueble extends Model
{
    use InmuebleTrait;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'direccion',
        'persona_id_administrador_referente',
        'persona_id_inquilino_referente',
        'persona_id_propietario_referente',
        'nombre_y_apellidos_administrador_referente',
        'nombre_y_apellidos_inquilino_referente',
        'nombre_y_apellidos_propietario_referente',
        'tipo',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'solicitud_directa_inquilinos' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'area',
	'area_estacionamiento',
	'cta_cte_catastral',
        'descripcion',
        'enum_estado',
        'enum_tabla_hija',
        'id_tabla_hija',
        'id_medida',
        'id_tipo_inmueble',
        'id_usuario_creador',
        'solicitud_directa_inquilinos',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inmuebles';

    protected $with = ['featured_photo', 'idInmueblePadre', 'idMedida', 'idTipoInmueble', 'unfeatured_photos'];

    /**
     * Get the administradores for the inmueble.
     */
    public function administradores()
    {
        return $this->hasMany(InmueblePersona::class, 'id_inmueble')->where('enum_estado', 'ACTIVO')->where('enum_rol', 'ADMINISTRADOR');
    }

    /**
     * The caracteristicas that belong to the inmueble.
     */
    public function caracteristicas()
    {
        return $this->belongsToMany(Caracteristica::class, 'caracteristica_inmueble', 'id_inmueble', 'id_caracteristica')
            ->withPivot(['cantidad', 'enum_estado', 'id_caracteristica_tipo_inmueble']);
    }

    /**
     * Get the documentos for the inmueble.
     */
    public function documentos()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * The tipos caracteristica that belong to the inmueble.
     */
    public function tipos_caracteristica()
    {
        return $this->belongsToMany(TipoInmueble::class, 'caracteristica_tipo_inmueble', 'id_caracteristica', 'id_tipo_inmueble');
    }

    /**
     * Get the espacios comunes for the inmueble.
     */
    public function espacios_comunes()
    {
        return $this->hasMany(Espacio::class, 'id_inmueble');
    }

    /**
     * Get the eventos for the inmueble.
     */
    public function eventos()
    {
        return $this->hasMany(Evento::class, 'id_inmueble');
    }

    /**
     * Get the investment's featured photo.
     */
    public function featured_photo()
    {
        return $this->morphOne(FeaturedPhoto::class, 'photoable')->withDefault();
    }

    /**
     * Get the investment's featured photo.
     */
    public function unfeatured_photos()
    {
        return $this->morphMany(UnfeaturedPhoto::class, 'photoable');
    } 

    /**
     * The formatos that belong to the inmueble.
     */
    public function formatos()
    {
        return $this->belongsToMany(Formato::class, 'formato_inmueble', 'id_inmueble', 'id_formato');
    }

    /**
     * Get the inmueble that owns the inmueble.
     */
    public function idInmueblePadre()
    {
        return $this->belongsTo(InmueblePadre::class, 'id_tabla_hija');
    }

    /**
     * Get the administrador referente record associated with the inmueble.
     */
    public function idAdministradorReferente()
    {
        return $this->hasOne(InmueblePersona::class, 'id_inmueble')->where('enum_estado', 'ACTIVO')->where('enum_rol', 'ADMINISTRADOR')->where('referente', true);
    }

    /**
     * Get the inquilino referente record associated with the inmueble.
     */
    public function idInquilinoReferente()
    {
        return $this->hasOne(InmueblePersona::class, 'id_inmueble')->where('enum_estado', 'ACTIVO')->where('enum_rol', 'INQUILINO')->where('referente', true);
    }

    /**
     * Get the medida that owns the inmueble.
     */
    public function idMedida()
    {
        return $this->belongsTo(Medida::class, 'id_medida');
    }

    /**
     * Get the propietario referente record associated with the inmueble.
     */
    public function idPropietarioReferente()
    {
        return $this->hasOne(InmueblePersona::class, 'id_inmueble')->where('enum_estado', 'ACTIVO')->where('enum_rol', 'PROPIETARIO')->where('referente', true);
    }

    /**
     * Get the tipo inmueble that owns the inmueble.
     */
    public function idTipoInmueble()
    {
        return $this->belongsTo(TipoInmueble::class, 'id_tipo_inmueble');
    }

    public function idUnidad()
    {
        return $this->belongsTo(Unidad::class, 'id_tabla_hija');
    }

    /**
     * Get the usuario creador that owns the inmueble.
     */
    public function idUsuarioCreador()
    {
        return $this->belongsTo(User::class, 'id_usuario_creador');
    }

    /**
     * Get the inquilinos for the inmueble.
     */
    public function inquilinos()
    {
        return $this->hasMany(InmueblePersona::class, 'id_inmueble')->where('enum_estado', 'ACTIVO')->where('enum_rol', 'INQUILINO');
    }

    /**
     * Get the nominaciones for the inmueble.
     */
    public function nominaciones()
    {
        return $this->hasMany(Nominacion::class, 'id_inmueble');
    }

    /**
     * The personas that belong to the inmueble.
     */
    public function personas()
    {
        return $this->belongsToMany(Persona::class, 'inmueble_persona', 'id_inmueble', 'id_persona')
            ->withPivot(['dia_cobro_mensual','enum_estado','enum_rol','fecha_fin_contrato','fecha_inicio_contrato','id_moneda_salario','referente','salario']);
    }

    /**
     * Get the propietarios for the inmueble.
     */
    public function propietarios()
    {
        return $this->hasMany(InmueblePersona::class, 'id_inmueble')->where('enum_estado', 'ACTIVO')->where('enum_rol', 'PROPIETARIO');
    }

    /**
     * Get the rentas for the inmueble.
     */
    public function rentas()
    {
        return $this->hasMany(Renta::class, 'id_inmueble');
    }

    /**
     * Get the pagares for the inmueble.
     */
    public function pagares()
    {
        return $this->hasMany(Pagare::class, 'id_inmueble');
    }

    /**
     * Get the unidades for the inmueble.
     */
    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'id_inmueble');
    }

}
