<?php

function e($val)
{
    echo htmlspecialchars($val, ENT_QUOTES);
}

function ucw($str)
{
    echo ucwords(str_replace('-', ' ', $str));
}
