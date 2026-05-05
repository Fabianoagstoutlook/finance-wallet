@component('mail::message')
# Ola, {{ $user->name }}

Seu saldo chegou a R$ 0,00.

@component('mail::button', ['url' => route('wallet.view')])
Ver carteira
@endcomponent

Obrigado,
{{ config('app.name') }}
@endcomponent
