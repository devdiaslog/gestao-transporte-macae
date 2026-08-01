<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => UserStatus::Active,
            'role' => UserRole::Operador,
        ];
    }

    /**
     * Por padrão o usuário de teste é Administrador (acesso total), evitando
     * que cada teste precise montar permissões. Use ->comPerfil('Operador')
     * ou ->semPerfil() para exercitar restrições de acesso.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (Role::where('name', 'Administrador')->exists()) {
                $user->assignRole('Administrador');
            }
        });
    }

    /** Cria o usuário com um perfil específico. */
    public function comPerfil(string $perfil): static
    {
        return $this->afterCreating(function (User $user) use ($perfil) {
            $user->syncRoles(Role::where('name', $perfil)->exists() ? [$perfil] : []);
        });
    }

    /** Cria o usuário sem nenhum perfil (nenhuma permissão). */
    public function semPerfil(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles([]));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
