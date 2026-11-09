<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates an account from the console, which works even while registration is closed in
 * demo mode. Options skip their questions; the password is always asked for, so it never
 * lands in shell history. See section B12 of docs/spec.md.
 */
#[Signature('user:create
    {--name= : Display name}
    {--email= : Email address used to log in}
    {--timezone= : IANA timezone, such as America/Chicago (default UTC)}')]
#[Description('Create a user account from the console')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('Run this in an interactive terminal, so it can ask for the password.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->option('name') ?? text('Name', required: true, validate: $this->check('name')),
            'email' => $this->option('email') ?? text('Email', required: true, validate: $this->check('email')),
            'password' => password('Password', required: true, validate: $this->check('password'), hint: 'At least 8 characters.'),
        ];

        $data['password_confirmation'] = password('Confirm password', required: true);
        $data['timezone'] = $this->option('timezone') ?? (text('Timezone', placeholder: 'UTC', validate: fn (string $value) => $value === '' ? null : $this->check('timezone')($value), hint: 'Press Enter for UTC, or type one such as America/Chicago.') ?: 'UTC');

        // Options skip the questions' checks, so everything is validated again here.
        $validator = Validator::make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => $data['password'],
            'timezone' => $data['timezone'],
        ]);

        // Made by the site owner, so the address doesn't need confirming.
        $user->markEmailAsVerified();

        $this->components->info("Created {$user->email}. They can log in now.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // The demo email is kept free, because demo:reset refuses to overwrite a real account.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', Rule::notIn(array_filter([config('relay.demo.email')]))],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'timezone' => ['required', 'timezone'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return ['email.not_in' => 'That email is reserved for the demo account.'];
    }

    /**
     * Validates one answer as it is typed, so a mistake is caught before the next question.
     */
    private function check(string $field): \Closure
    {
        return function (string $value) use ($field): ?string {
            $rules = array_filter($this->rules()[$field], fn ($rule) => $rule !== 'confirmed');

            return Validator::make([$field => $value], [$field => $rules], $this->messages())->errors()->first($field) ?: null;
        };
    }
}
