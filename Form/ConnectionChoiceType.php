<?php


namespace Mapbender\QueryBuilderBundle\Form;


use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectionChoiceType extends AbstractType
{
    /** @var RegistryInterface */
    protected $connectionRegistry;

    public function __construct(RegistryInterface $connectionRegistry)
    {
        $this->connectionRegistry = $connectionRegistry;
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
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
