<?php

namespace App\Console\Commands;

use App\Branch;
use App\Company;
use App\Tenant;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BootstrapDefaultTenant extends Command
{
    protected $signature = 'multitenancy:bootstrap-default
        {--tenant-name= : Nombre del grupo empresarial}
        {--tenant-slug= : Identificador único del tenant}
        {--ruc= : RUC de la empresa}
        {--business-name= : Razón social}
        {--trade-name= : Nombre comercial}
        {--company-address= : Dirección fiscal}
        {--company-phone= : Teléfono de la empresa}
        {--company-email= : Correo de la empresa}
        {--branch-code=PRINCIPAL : Código del local principal}
        {--branch-name=Local Principal : Nombre del local principal}
        {--branch-address= : Dirección del local}
        {--branch-phone= : Teléfono del local}';

    protected $description =
        'Crea el tenant, empresa y local predeterminados y asigna los usuarios actuales.';

    public function handle()
    {
        $tenantName = trim(
            (string) $this->option('tenant-name')
        );

        $tenantSlug = trim(
            (string) $this->option('tenant-slug')
        );

        $ruc = preg_replace(
            '/\D/',
            '',
            (string) $this->option('ruc')
        );

        $businessName = trim(
            (string) $this->option('business-name')
        );

        $tradeName = trim(
            (string) $this->option('trade-name')
        );

        $branchCode = mb_strtoupper(
            trim((string) $this->option('branch-code')),
            'UTF-8'
        );

        $branchName = trim(
            (string) $this->option('branch-name')
        );

        if ($tenantName === '') {
            $this->error(
                'Debe indicar --tenant-name.'
            );

            return 1;
        }

        if ($tenantSlug === '') {
            $tenantSlug = Str::slug($tenantName);
        }

        if (!preg_match('/^\d{11}$/', $ruc)) {
            $this->error(
                'Debe indicar un RUC válido de 11 dígitos mediante --ruc.'
            );

            return 1;
        }

        if ($businessName === '') {
            $this->error(
                'Debe indicar --business-name.'
            );

            return 1;
        }

        if ($branchCode === '') {
            $this->error(
                'Debe indicar un código válido para el local.'
            );

            return 1;
        }

        if ($branchName === '') {
            $this->error(
                'Debe indicar un nombre válido para el local.'
            );

            return 1;
        }

        try {
            $result = DB::transaction(function () use (
                $tenantName,
                $tenantSlug,
                $ruc,
                $businessName,
                $tradeName,
                $branchCode,
                $branchName
            ) {
                /*
                 * El slug identifica de forma estable al tenant.
                 * firstOrCreate hace que el comando pueda ejecutarse
                 * nuevamente sin duplicar registros.
                 */
                $tenant = Tenant::firstOrCreate(
                    [
                        'slug' => $tenantSlug,
                    ],
                    [
                        'name' => $tenantName,
                        'is_active' => true,
                    ]
                );

                /*
                 * Si el tenant ya existía, actualizamos únicamente
                 * los datos que deben mantenerse vigentes.
                 */
                $tenant->name = $tenantName;
                $tenant->is_active = true;
                $tenant->save();

                $company = Company::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'ruc' => $ruc,
                    ],
                    [
                        'business_name' => $businessName,

                        'trade_name' =>
                            $tradeName !== ''
                                ? $tradeName
                                : null,

                        'address' =>
                            $this->nullableOption(
                                'company-address'
                            ),

                        'phone' =>
                            $this->nullableOption(
                                'company-phone'
                            ),

                        'email' =>
                            $this->nullableOption(
                                'company-email'
                            ),

                        'is_active' => true,
                    ]
                );

                $company->business_name = $businessName;

                $company->trade_name =
                    $tradeName !== ''
                        ? $tradeName
                        : $company->trade_name;

                $company->address =
                    $this->nullableOption(
                        'company-address'
                    ) ?: $company->address;

                $company->phone =
                    $this->nullableOption(
                        'company-phone'
                    ) ?: $company->phone;

                $company->email =
                    $this->nullableOption(
                        'company-email'
                    ) ?: $company->email;

                $company->is_active = true;
                $company->save();

                $branch = Branch::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $branchCode,
                    ],
                    [
                        'name' => $branchName,

                        'address' =>
                            $this->nullableOption(
                                'branch-address'
                            ),

                        'phone' =>
                            $this->nullableOption(
                                'branch-phone'
                            ),

                        'is_main' => true,
                        'is_active' => true,
                    ]
                );

                $branch->name = $branchName;

                $branch->address =
                    $this->nullableOption(
                        'branch-address'
                    ) ?: $branch->address;

                $branch->phone =
                    $this->nullableOption(
                        'branch-phone'
                    ) ?: $branch->phone;

                $branch->is_main = true;
                $branch->is_active = true;
                $branch->save();

                $assignedUsers = 0;

                /*
                 * Los usuarios actuales pertenecen al negocio
                 * que estamos convirtiendo al primer tenant.
                 *
                 * Los platform admins se excluyen porque no deben
                 * quedar operativamente ligados a un cliente.
                 */
                User::query()
                    ->where('is_platform_admin', false)
                    ->orderBy('id')
                    ->chunkById(
                        100,
                        function ($users) use (
                            $tenant,
                            $company,
                            $branch,
                            &$assignedUsers
                        ) {
                            foreach ($users as $user) {
                                $user->tenant_id = $tenant->id;
                                $user->save();

                                /*
                                 * syncWithoutDetaching conserva
                                 * asignaciones existentes.
                                 */
                                $user->companies()
                                    ->syncWithoutDetaching([
                                        $company->id => [
                                            'is_default' => true,
                                            'is_active' => true,
                                        ],
                                    ]);

                                $user->branches()
                                    ->syncWithoutDetaching([
                                        $branch->id => [
                                            'is_default' => true,
                                            'is_active' => true,
                                        ],
                                    ]);

                                $assignedUsers++;
                            }
                        }
                    );

                return [
                    'tenant' => $tenant,
                    'company' => $company,
                    'branch' => $branch,
                    'assigned_users' => $assignedUsers,
                ];
            });

            $this->info(
                'Contexto predeterminado creado correctamente.'
            );

            $this->table(
                [
                    'Entidad',
                    'ID',
                    'Descripción',
                ],
                [
                    [
                        'Tenant',
                        $result['tenant']->id,
                        $result['tenant']->name,
                    ],
                    [
                        'Company',
                        $result['company']->id,
                        $result['company']->business_name .
                        ' - RUC ' .
                        $result['company']->ruc,
                    ],
                    [
                        'Branch',
                        $result['branch']->id,
                        $result['branch']->name,
                    ],
                    [
                        'Usuarios asignados',
                        $result['assigned_users'],
                        'Usuarios no pertenecientes a plataforma',
                    ],
                ]
            );

            return 0;

        } catch (\Throwable $e) {
            $this->error(
                'No se pudo crear el contexto predeterminado: ' .
                $e->getMessage()
            );

            report($e);

            return 1;
        }
    }

    private function nullableOption($name)
    {
        $value = trim(
            (string) $this->option($name)
        );

        return $value !== ''
            ? $value
            : null;
    }
}
