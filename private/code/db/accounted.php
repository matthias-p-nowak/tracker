<?php
namespace Code\Db;


class Accounted {
    public string $Activity;
    public int $Day;
    public int $YearWeek;
    public int $WeekDay;
    public float $Sofar;
    public float $Accounted;
    public float $DayAccount;
    public float $DayTotal;
    public DbCtx $ctx;
}