<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormFactory
{
    private $formFactory;

    private $name;

    private $type;

    public function __construct(FormFactoryInterface $formFactory, string $name, string $type)
    {
        $this->formFactory = $formFactory;
        $this->name = $name;
        $this->type = $type;
    }

    public function createForm(array $options = [], $data = null): FormInterface
    {
        $form = $this->formFactory->createNamed($this->name, $this->type, null, $options);
        if (null !== $data) {
            $form->setData($data);
        }

        return $form;
    }
}
