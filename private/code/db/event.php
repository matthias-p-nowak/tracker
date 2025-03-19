<?php

namespace Code\Db;

class Event{
    public int $Id;
    public ?string $Activity;
    public ?string $Details;
    public ?string $Started;
    public ?string $Ended;
    public ?string $IP;
    public ?float $Latitude;
    public ?float $Longitude;
    public DbCtx $ctx;
}