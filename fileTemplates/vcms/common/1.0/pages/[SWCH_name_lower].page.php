<?php

Class [SWCH_name_upper]_Page extends PageInterface
{
    public function index()
    {
        /** @var [SWCH_name_upper]_Component $component */
        $component = $this->components['[SWCH_name_upper]'];

        $component->html = $component->renderIndex();
    }
}