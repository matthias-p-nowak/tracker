<?php
namespace Code\Db;

class Activity {
    public string $Activity;
    public ?string $Parent;
    public int $Results;
    public DbCtx $ctx;
}