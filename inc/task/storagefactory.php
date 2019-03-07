<?php
interface IStorageFactory
{
    public function createStorageMedia();
}

class FileStorageFactory implements IStorageFactory
{
    public function createStorageMedia()
    {
        return new FileStorage();
    }
}
