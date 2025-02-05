<?php


namespace Mapbender\QueryBuilderBundle\Form;


use Doctrine\Persistence\ConnectionRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectionChoiceType extends AbstractType
{
    public function __construct(protected ConnectionRegistry $connectionRegistry)
    {
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $registry = $this->connectionRegistry;
        $resolver->setDefaults(array(
            'choices' => function(Options $options) use ($registry) {
                $choices = array();
                foreach (\array_keys($registry->getConnectionNames()) as $name) {
                    $choices[$name] = $name;
                }
                return $choices;
            }
        ));
    }
}
