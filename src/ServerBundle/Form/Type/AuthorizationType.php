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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AuthorizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translator = $options['translator'];
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
        /*if (true === $options['is_pre_configured_authorization_enabled']) {
            $builder
                ->add('save_configuration', CheckboxType::class, [
                    'label'    => $translator->trans('authorization.form.save', [], $options['translation_domain'], $options['locale']),
                    'required' => false,
                ]);
        }*/
        $builder
            ->add('accept', SubmitType::class, [
                'label' => $translator->trans('authorization.form.accept', [], $options['translation_domain'], $options['locale']),
            ])
            ->add('reject', SubmitType::class, [
                'label' => $translator->trans('authorization.form.reject', [], $options['translation_domain'], $options['locale']),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'OAuth2FrameworkServer',
            'data_class' => 'OAuth2Framework\ServerBundle\Form\Model\AuthorizationModel',
            'scopes' => [],
            'allow_scope_selection' => false,
            'is_pre_configured_authorization_enabled' => false,
            'locale' => null,
            'translator' => null,
        ]);
        $resolver->setAllowedTypes('locale', ['string', 'null']);
        $resolver->setAllowedTypes('scopes', 'array');
        $resolver->setAllowedTypes('allow_scope_selection', 'bool');
        $resolver->setAllowedTypes('is_pre_configured_authorization_enabled', 'bool');
        $resolver->setAllowedTypes('translator', TranslatorInterface::class);
    }

    public function getBlockPrefix()
    {
        return 'oauth2_server_authorization';
    }
}
