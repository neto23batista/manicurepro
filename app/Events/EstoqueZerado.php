<?php

namespace App\Events;

use App\Models\Produto;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EstoqueZerado
{
    use Dispatchable, SerializesModels;

    public function __construct(public Produto $produto) {}
}
