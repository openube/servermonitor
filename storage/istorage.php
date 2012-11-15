<?php

namespace Storage;

/**
 * Interface must be implemented by any storage provider.
 * Defines crutual methods to get possible to operate with storage.
 */
interface IStorage
{
    /**
     * Method to get entry's ip or hostname
     */
    function getHost();

    /**
     * Method to get entry's port number
     */
    function getPort();

    /**
     * Method to get entry from storage. Iterates through storage if no key 
     * value was passed
     *
     * @return mixed server info entry
     */
    function fetch($key);

    /**
     * Reset internal fetch and put cursors
     */
    function resetCursor();

    /**
     * Method to put entry to the storage. Puts to the end of storage if no key 
     * value was passed
     */
    function put(\stdClass $entry, $key);

    /**
     * Method to save storage's state
     * Thanks, cap
     */
    function save();
}
