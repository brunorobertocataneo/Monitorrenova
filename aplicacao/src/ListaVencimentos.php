<?php
declare(strict_types=1);

final class ItemVencimento
{
    public function __construct(private readonly array $dados) {}
    public function dados(): array { return $this->dados; }
}

final class ListaVencimentos
{
    private array $itens = [];

    public function adicionar(string $vendedor, array $seguro): void
    {
        $this->itens[$vendedor][] = new ItemVencimento($seguro);
    }

    public function porVendedor(): array
    {
        $grupos = [];
        foreach ($this->itens as $vendedor => $itens) {
            foreach ($itens as $item) $grupos[$vendedor][] = $item->dados();
        }
        return $grupos;
    }
}
