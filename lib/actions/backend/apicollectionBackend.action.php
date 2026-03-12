<?php

declare(strict_types=1);

class apicollectionBackendAction extends waViewAction
{
    public function execute(): void
    {
        $this->view->assign([
            'contactId' => (int) wa()->getUser()->getId(),
        ]);

        wa()->getResponse()->addCss('css/apicollection.css', 'apicollection');
    }

}
