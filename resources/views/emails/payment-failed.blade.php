<h2>Hi {{ $user->name }},</h2>

<p>We tried to process your payment, but it failed.</p>

<p>Please update your billing information to avoid service interruption.</p>

<p><a href="{{ config('app.url') }}/billing" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">
Update Payment Method
</a></p>
