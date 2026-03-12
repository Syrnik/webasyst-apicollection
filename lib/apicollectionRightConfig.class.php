<?php

class apicollectionRightConfig extends waRightConfig
{
    public function init(): void
    {
        $this->addItem('manage_shared', 'Управление общими коллекциями');
    }
}
