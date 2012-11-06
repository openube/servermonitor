<?php

namespace Storage;

/**
 * Database storage interface
 *
 * As we suppose people use different database engines we want to be sure we 
 * can connect to mysq or postgre and get servers info from there
 */
interface IDatabase {

    /**
     * Connects to supported database server
     */
    private function connect();
}
