<?php
namespace Code\Db;


class Password {
    public string $Hash;
    public string $Cookie;
    public ?string $Created;
    public ?string $Used;
    public DbCtx $ctx;
}