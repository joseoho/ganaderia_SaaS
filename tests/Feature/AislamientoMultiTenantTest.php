<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalPotrero;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\Genealogia;
use App\Models\Inquilino;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Reporte;
use App\Models\Reproduccion;
use App\Models\Rotacion;
use App\Models\Scopes\TenantScope;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Vacuna;
use App\Models\Venta;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Gate 2 - Aislamiento multi-tenant.
 *
 * Estos tests son el contrato del aislamiento: si alguien anade un modelo de
 * negocio sin el trait BelongsToTenant, o rompe el filtro, aqui falla.
 */
class AislamientoMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<class-string> */
    private const MODELOS_DE_NEGOCIO = [
        Animal::class,
        AnimalPotrero::class,
        Compra::class,
        Gastos::class,
        Genealogia::class,
        InventarioInsumo::class,
        Movilizacion::class,
        Notificacion::class,
        Potrero::class,
        ProduccionCarne::class,
        ProduccionLeche::class,
        RegistroVacuna::class,
        Reporte::class,
        Reproduccion::class,
        Rotacion::class,
        Tratamiento::class,
        Vacuna::class,
        Venta::class,
    ];

    private Inquilino $ganaderiaA;

    private Inquilino $ganaderiaB;

    private User $usuarioA;

    protected function setUp(): void
    {
        parent::setUp();

        // DatabaseSeeder crea dos ganaderias con datos completos.
        $this->seed();

        $this->ganaderiaA = Inquilino::orderBy('id')->firstOrFail();
        $this->ganaderiaB = Inquilino::orderBy('id')->skip(1)->firstOrFail();
        $this->usuarioA = User::query()
            ->where('inquilino_id', $this->ganaderiaA->id)
            ->firstOrFail();

        app(TenantContext::class)->forget();
    }

    public function test_todos_los_modelos_de_negocio_tienen_el_filtro_de_ganaderia(): void
    {
        foreach (self::MODELOS_DE_NEGOCIO as $modelo) {
            $this->assertContains(
                TenantScope::class,
                array_keys((new $modelo)->getGlobalScopes()),
                "El modelo {$modelo} no aplica el aislamiento multi-tenant."
            );
        }
    }

    public function test_el_modelo_de_usuarios_no_lleva_el_filtro_y_es_a_proposito(): void
    {
        // Parece una excepcion rara, y es la correccion de un fallo grave.
        //
        // User llevaba el filtro global por ganaderia, pero la ganaderia se
        // deduce DEL usuario (de su sesion). Al entrar con el formulario, o al
        // recuperarlo de la sesion en cada peticion, hay que consultar la tabla
        // `users` ANTES de saber su ganaderia, asi que el filtro —que falla
        // cerrado— abortaba y NADIE podia iniciar sesion:
        //
        //     Consulta a [App\Models\User] sin ganaderia activa.
        //
        // Esta prueba existe para que no se lo vuelvan a poner.
        $this->assertNotContains(
            TenantScope::class,
            array_keys((new User)->getGlobalScopes()),
            'User no debe llevar el filtro por ganaderia: rompe el inicio de sesion. '
            .'El aislamiento de los datos de negocio lo cubren los demas modelos.'
        );

        // El aislamiento de los datos de negocio sigue intacto.
        $this->assertNotEmpty(self::MODELOS_DE_NEGOCIO);
    }

    public function test_ningun_modelo_devuelve_registros_de_otra_ganaderia(): void
    {
        $contexto = app(TenantContext::class);

        foreach (self::MODELOS_DE_NEGOCIO as $modelo) {
            $contexto->set($this->ganaderiaA->id);

            foreach ($modelo::pluck('inquilino_id') as $inquilinoId) {
                $this->assertSame(
                    $this->ganaderiaA->id,
                    (int) $inquilinoId,
                    "El modelo {$modelo} ha devuelto un registro de otra ganaderia."
                );
            }
        }
    }

    public function test_los_conteos_por_ganaderia_son_disjuntos_y_suman_el_total(): void
    {
        $contexto = app(TenantContext::class);

        // User NO entra aqui: no lleva el filtro global a proposito (romperia el
        // inicio de sesion) y por tanto no tiene withoutTenantScope(). Su
        // aislamiento se comprueba en SesionRealTest, entrando de verdad: la
        // sesion queda en la ganaderia correcta y los registros de otra no son
        // alcanzables.
        $modelos = [Animal::class, Potrero::class, Venta::class, ProduccionLeche::class, Notificacion::class];

        foreach ($modelos as $modelo) {
            $total = $modelo::withoutTenantScope()->count();

            $contexto->set($this->ganaderiaA->id);
            $deA = $modelo::count();

            $contexto->set($this->ganaderiaB->id);
            $deB = $modelo::count();

            $this->assertGreaterThan(0, $deA, "La ganaderia A no ve sus propios registros de {$modelo}.");
            $this->assertGreaterThan(0, $deB, "La ganaderia B no ve sus propios registros de {$modelo}.");
            $this->assertSame($total, $deA + $deB, "Los conteos de {$modelo} no cuadran con el total.");
        }
    }

    public function test_un_registro_de_otra_ganaderia_no_es_accesible_por_url(): void
    {
        $ajeno = Animal::withoutTenantScope()->where('inquilino_id', $this->ganaderiaB->id)->firstOrFail();
        $propio = Animal::withoutTenantScope()->where('inquilino_id', $this->ganaderiaA->id)->firstOrFail();

        // El filtro global hace que el route-model binding no lo encuentre: 404.
        $this->actingAs($this->usuarioA)->get("/animales/{$ajeno->id}")->assertNotFound();

        $this->assertNotNull(Animal::find($propio->id), 'La propia ganaderia si debe poder verlo.');
    }

    public function test_el_listado_no_muestra_registros_de_otra_ganaderia(): void
    {
        $codigoAjeno = Animal::withoutTenantScope()
            ->where('inquilino_id', $this->ganaderiaB->id)
            ->value('codigo_interno');
        $codigoPropio = Animal::withoutTenantScope()
            ->where('inquilino_id', $this->ganaderiaA->id)
            ->value('codigo_interno');

        $respuesta = $this->actingAs($this->usuarioA)->get('/animales');

        $respuesta->assertOk();
        $respuesta->assertSee($codigoPropio);
        $respuesta->assertDontSee($codigoAjeno);
    }

    public function test_al_crear_un_registro_se_asigna_la_ganaderia_activa(): void
    {
        app(TenantContext::class)->set($this->ganaderiaB->id);

        $animal = Animal::create([
            'codigo_interno' => 'AUTO-ASIGNADO',
            'categoria' => 'bovino',
            'raza' => 'Test',
            'fecha_nacimiento' => '2026-01-01',
            'sexo' => 'M',
            'estado' => 'activo',
        ]);

        $this->assertSame($this->ganaderiaB->id, $animal->inquilino_id);
        $this->assertNull(
            Animal::withoutTenantScope()->where('codigo_interno', 'AUTO-ASIGNADO')
                ->where('inquilino_id', '!=', $this->ganaderiaB->id)->first()
        );
    }

    public function test_sin_ganaderia_activa_las_consultas_fallan_cerrado(): void
    {
        app(TenantContext::class)->forget();
        TenantScope::$forceFailClosed = true;

        try {
            $this->expectException(RuntimeException::class);
            Animal::count();
        } finally {
            TenantScope::$forceFailClosed = null;
        }
    }

    public function test_el_acceso_explicito_sin_filtro_si_ve_todas_las_ganaderias(): void
    {
        $total = Animal::withoutTenantScope()->count();
        $soloA = Animal::withoutTenantScope()->where('inquilino_id', $this->ganaderiaA->id)->count();

        $this->assertGreaterThan($soloA, $total, 'withoutTenantScope debe ver ambas ganaderias.');
    }
}
