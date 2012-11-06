<?php

namespace Storage;

interface IStorage
{
    function fetch($key);

    function put($entry, $key);

    function save();
}
