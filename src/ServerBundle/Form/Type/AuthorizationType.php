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

namespace OAuth2Framework\ServerBundle\Form\Type;

use OAuth2Framework\ServerBundle\Form\Model\AuthorizationModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthorizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*if (true === $options['allow_scope_selection']) {
            $builder->add('scopes', ChoiceType::class, [
                'label'             => $translator->trans('authorization.form.scope', [], $options['translation_domain'], $options['locale']),
                'multiple'          => 'true',
                'expanded'          => 'true',
                'required'          => false,
                'choices'           => $options['scopes'],
                'choices_as_values' => true,
                'choice_label'      => function ($allChoices, $key) use ($options) {
                    return $translator->trans('authorization.form.scope.'.$allChoices, [], $options['translation_domain'], $options['locale']);
                },
                'choice_name' => function ($allChoices, $key) {
                    return $allChoices;
                },
            ]);
        }*/
        $builder
            ->add('accept', SubmitType::class, [
                'label' => 'authorization.form.accept',
            ])
            ->add('reject', SubmitType::class, [
                'label' => 'authorization.form.reject',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'OAuth2FrameworkServer',
            'data_class' => AuthorizationModel::class,
            'scopes' => [],
            'locale' => null,
        ]);
        $resolver->setAllowedTypes('locale', ['string', 'null']);
        $resolver->setAllowedTypes('scopes', 'array');
    }

    public function getBlockPrefix()
    {
        return 'oauth2_server_authorization';
    }
}
