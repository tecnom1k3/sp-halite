<?php
namespace Acme\Service;

class User
{
    public function getList()
    {
        return [
            ['id' => '1', 'name' => 'John Doe'],
            ['id' => '2', 'name' => 'Jane Smith'],
        ];
    }
}
