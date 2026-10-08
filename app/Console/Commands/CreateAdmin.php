<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email?} {--name=}';

    protected $description = 'Create a CMS administrator with an interactive, hidden password';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email admin');
        $name = $this->option('name') ?? $this->ask('Nama admin');
        $password = $this->secret('Password (minimal 12 karakter, huruf besar/kecil, angka dan simbol)');
        $confirmation = $this->secret('Ulangi password');
        $validator = Validator::make(['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirmation], ['email' => ['required', 'email', 'max:254', 'unique:users,email'], 'name' => ['required', 'string', 'max:255'], 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User;
        $user->fill(['email' => $email, 'name' => $name, 'password' => $password]);
        $user->forceFill(['is_admin' => true])->save();
        $this->info('Akun admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
