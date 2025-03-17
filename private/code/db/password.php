<?php
namespace Code\Db;

#[\AllowDynamicProperties]
class Password {
    public string $Hash;
    public string $Cookie;
}