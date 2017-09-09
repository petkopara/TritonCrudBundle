<?php

/*
 * This file is part of the PetkoparaCrudGeneratorBundle
 *
 * (c) Petko Petkov <petkopara@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Petkopara\CrudGeneratorBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Petkopara\CrudGeneratorBundle\Configuration\ConfigurationBuilder;
use Petkopara\CrudGeneratorBundle\Generator\Guesser\MetadataGuesser;
use Petkopara\CrudGeneratorBundle\Generator\PetkoparaCrudGenerator;
use Petkopara\CrudGeneratorBundle\Generator\PetkoparaFilterGenerator;
use Petkopara\CrudGeneratorBundle\Generator\PetkoparaFormGenerator;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCrudCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Exception\RuntimeException;

class CrudGeneratorCommand extends GenerateDoctrineCrudCommand
{

    const FILTER_TYPE_FORM = 'form';
    const FILTER_TYPE_INPUT = 'input';
    const FILTER_TYPE_NONE = 'none';

    protected $generator;
    protected $formGenerator;
    private $filterGenerator;

    protected function configure()
    {

        $this
                ->setName('petkopara:generate:crud')
                ->setDescription('A CRUD generator with pagination, filters, bulk delete and bootstrap markdown.')
                ->setDefinition(array(
                    new InputArgument('entity', InputArgument::OPTIONAL, 'The entity class name to initialize (shortcut notation)'),
                    new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The entity class name to initialize (shortcut notation)'),
                    new InputOption('route-prefix', 'r', InputOption::VALUE_REQUIRED, 'The route prefix'),
                    new InputOption('template', 't', InputOption::VALUE_REQUIRED, 'The base template which will be extended by the templates', 'PetkoparaCrudGeneratorBundle::base.html.twig'),
                    new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format used for configuration files (php, xml, yml, or annotation)', 'annotation'),
                    new InputOption('overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite any existing controller or form class when generating the CRUD contents'),
                    new InputOption('bundle-views', 'b', InputOption::VALUE_NONE, 'Whether or not to store the view files in app/Resources/views/ or in bundle dir'),
                    new InputOption('without-sorting', 'wsr', InputOption::VALUE_NONE, 'Whether or not have sorting in the index'),
                    new InputOption('without-page-size', 'wps', InputOption::VALUE_NONE, 'Whether or not to show items per page select in the index'),
                    new InputOption('without-write', 'ww', InputOption::VALUE_NONE, 'Whether or not to generate create, new and delete actions'),
                    new InputOption('without-show', 'ws', InputOption::VALUE_NONE, 'Whether or not to generate create, new and delete actions'),
                    new InputOption('without-bulk', 'wb', InputOption::VALUE_NONE, 'Whether or not to generate bulk actions'),
                    new InputOption('filter-type', 'ft', InputOption::VALUE_REQUIRED, 'What type of filtrations to be used. Multi search input, Form filter or none', 'form'),
                ))
                ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a CRUD based on a Doctrine entity.

The default command only generates the list and show actions.

<info>php %command.full_name% --entity=AcmeBlogBundle:Post --route-prefix=post_admin</info>

Using the --without-write to not generate the new, edit, bulk and delete actions.
                
Using the --bundle-views option store the view files in the bundles dir.
                
Using the --without-bulk  use this option tp not generate bulk actions code.
                
Using the --template option allows to set base template from which the crud views to overide.
                
<info>php %command.full_name% doctrine:generate:crud --entity=AcmeBlogBundle:Post --route-prefix=post_admin </info>

Every generated file is based on a template. There are default templates but they can be overridden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/CrudGeneratorBundle/skeleton/crud
APP_PATH/Resources/CrudGeneratorBundle/skeleton/crud</info>

And

<info>__bundle_path__/Resources/CrudGeneratorBundle/skeleton/form
__project_root__/app/Resources/CrudGeneratorBundle/skeleton/form</info>

EOT
        );
    }

    protected function createGenerator($bundle = null)
    {
        return new PetkoparaCrudGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->getParameter('kernel.root_dir'));
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = array();
        if (isset($bundle) && is_dir($dir = $bundle->getPath() . '/Resources/PetkoparaCrudGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir() . '/Resources/PetkoparaCrudGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $skeletonDirs[] = $this->getContainer()->get('kernel')->locateResource('@PetkoparaCrudGeneratorBundle/Resources/skeleton');
        $skeletonDirs[] = $this->getContainer()->get('kernel')->locateResource('@PetkoparaCrudGeneratorBundle/Resources');

        return $skeletonDirs;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {

        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Petkopara CRUD generator');

        // namespace
        $output->writeln(array(
            '',
            'This command helps you generate CRUD controllers and templates.',
            '',
            'First, give the name of the existing entity for which you want to generate a CRUD',
            '(use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>)',
            '',
        ));

        if ($input->hasArgument('entity') && $input->getArgument('entity') != '') {
            $input->setOption('entity', $input->getArgument('entity'));
        }

        $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getOption('entity')), $input->getOption('entity'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));


        $autocompleter = new EntitiesAutoCompleter($this->getContainer()->get('doctrine')->getManager());
        $autocompleteEntities = $autocompleter->getSuggestions();
        $question->setAutocompleterValues($autocompleteEntities);
        $entity = $questionHelper->ask($input, $output, $question);

        $input->setOption('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
            $this->getEntityMetadata($entityClass);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. You may have mistyped the bundle name or maybe the entity doesn\'t exist yet (create it first with the "doctrine:generate:entity" command).', $entity, $bundle));
        }

        // write?
        $withoutWrite = $input->getOption('without-write') ? true : false; //default false
        $output->writeln(array(
            '',
            'By default, the generator creates all actions: list and show, new, update, and delete.',
            'You can also skip it and to generate only "list and show" actions:',
            '',
        ));
        $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you want to skip generating of the "write" actions', $withoutWrite ? 'yes' : 'no', '?', $withoutWrite), $withoutWrite);
        $withoutWrite = $questionHelper->ask($input, $output, $question);
        $input->setOption('without-write', $withoutWrite);

        // filters?
        $filterType = $input->getOption('filter-type');
        $output->writeln(array(
            '',
            'By default, the generator generate filter code.',
             '<comment>form</comment> to use LexikFormFilterBundle to search in the entity.',
             '<comment>input</comment> to use PetkoparaMultiSearchBundle to search only with one input in the entity.',
             '<comment>none</comment> use this to not generate any filter code.',
            '',
        ));
        $question = new Question($questionHelper->getQuestion('Filter Type (form, input, none)', $filterType), $filterType);
        $question->setValidator(array('Petkopara\CrudGeneratorBundle\Command\CrudValidators', 'validateFilterType'));
        $filterType = $questionHelper->ask($input, $output, $question);
        $input->setOption('filter-type', $filterType);


        // template?
        $template = $input->getOption('template');
        $output->writeln(array(
            '',
            'By default, the created views extends the CrudGeneratorBundle::base.html.twig',
            'You can also set your template which the views to extend, for example base.html.twig ',
            '',
        ));
        $question = new Question($questionHelper->getQuestion('Base template for the views', $template), $template);
        $template = $questionHelper->ask($input, $output, $question);
        $input->setOption('template', $template);


        // format
        $format = $input->getOption('format');
        $output->writeln(array(
            '',
            'Determine the format to use for the generated CRUD.',
            '',
        ));
        $question = new Question($questionHelper->getQuestion('Configuration format (yml, xml, php, or annotation)', $format), $format);
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateFormat'));
        $format = $questionHelper->ask($input, $output, $question);
        $input->setOption('format', $format);

        // route prefix
        $prefix = $this->getRoutePrefix($input, $entity);
        $output->writeln(array(
            '',
            'Determine the routes prefix (all the routes will be "mounted" under this',
            'prefix: /prefix/, /prefix/new, ...).',
            '',
        ));
        $prefix = $questionHelper->ask($input, $output, new Question($questionHelper->getQuestion('Routes prefix', '/' . $prefix), '/' . $prefix));
        $input->setOption('route-prefix', $prefix);

        // summary
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf('You are going to generate a CRUD controller for "<info>%s:%s</info>"', $bundle, $entity),
            sprintf('Using the "<info>%s</info>" format.', $format),
            sprintf('Route prefix "<info>%s</info>" format.', $prefix),
            sprintf('Base template "<info>%s</info>".', $template),
            sprintf('With write "<info>%s</info>".', (!$withoutWrite) ? 'yes' : 'no'),
            sprintf('Filters "<info>%s</info>".', $filterType),
            '',
        ));
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');
                return 1;
            }
        }

        $entity = Validators::validateEntityName($input->getOption('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        //get the options
        $format = Validators::validateFormat($input->getOption('format'));
        $prefix = $this->getRoutePrefix($input, $entity);
        $withoutWrite = $input->getOption('without-write'); //default with write
        $filterType = CrudValidators::validateFilterType($input->getOption('filter-type'));
        $withoutBulk = $input->getOption('without-bulk');
        $withoutShow = $input->getOption('without-show');
        $withoutSorting = $input->getOption('without-sorting');
        $withoutPageSize = $input->getOption('without-page-size');
        $bundleViews = $input->getOption('bundle-views');
        $template = $input->getOption('template');

        $forceOverwrite = $input->getOption('overwrite');

        $questionHelper->writeSection($output, 'CRUD generation');

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
            $metadata = $this->getEntityMetadata($entityClass);
        } catch (Exception $e) {
            throw new RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. Create it with the "doctrine:generate:entity" command and then execute this command again.', $entity, $bundle));
        }

        $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);


        $configBuilder = new ConfigurationBuilder();
        $configuration = $configBuilder
                ->setBaseTemplate($template)
                ->setBundleViews($bundleViews)
                ->setFilterType($filterType)
                ->setWithoutWrite($withoutWrite)
                ->setWithoutBulk($withoutBulk)
                ->setWithoutShow($withoutShow)
                ->setWithoutSorting($withoutSorting)
                ->setWithoutPageSize($withoutPageSize)
                ->setOverwrite($forceOverwrite)
                ->setFormat($format)
                ->setRoutePrefix($prefix)
                ->getConfiguration()
        ;
        $generator = $this->getGenerator($bundle);

        $generator->generateCrud($bundle, $entity, $metadata[0], $configuration);

        $output->writeln('Generating the CRUD code: <info>OK</info>');

        $errors = array();
        $runner = $questionHelper->getRunner($output, $errors);

        // form
        if ($withoutWrite === false) {
            $this->generateForm($bundle, $entity, $metadata, $forceOverwrite);
            $output->writeln('Generating the Form code: <info>OK</info>');
        }

        if ($filterType !== self::FILTER_TYPE_NONE) {

            $this->generateFilter($bundle, $entity, $metadata, $forceOverwrite, $filterType);
            $output->writeln('Generating the Filter code: <info>OK</info>');
        }
        // routing
        $output->write('Updating the routing: ');
        if ('annotation' != $format) {
            $runner($this->updateRouting($questionHelper, $input, $output, $bundle, $format, $entity, $prefix));
        } else {
            $runner($this->updateAnnotationRouting($bundle, $entity, $prefix));
        }

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    /**
     * Tries to generate filtlers if they don't exist yet and if we need write operations on entities.
     * @param string $entity
     */
    protected function generateFilter($bundle, $entity, $metadata, $forceOverwrite = false, $type = self::FILTER_TYPE_INPUT)
    {
        $this->getFilterGenerator($bundle)->generate($bundle, $entity, $metadata[0], $forceOverwrite, $type);
    }

    protected function getFilterGenerator($bundle = null)
    {
        if (null === $this->filterGenerator) {
            $metadataGuesser = new MetadataGuesser(new DisconnectedMetadataFactory($this->getContainer()->get('doctrine')));
            $this->filterGenerator = new PetkoparaFilterGenerator($metadataGuesser);
            $this->filterGenerator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->filterGenerator;
    }

    protected function getFormGenerator($bundle = null)
    {
        if (null === $this->formGenerator) {
            $metadataGuesser = new MetadataGuesser(new DisconnectedMetadataFactory($this->getContainer()->get('doctrine')));
            $this->formGenerator = new PetkoparaFormGenerator($metadataGuesser);
            $this->formGenerator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->formGenerator;
    }

    public function setFilterGenerator(PetkoparaFilterGenerator $filterGenerator)
    {
        $this->filterGenerator = $filterGenerator;
    }

    public function setFormCrudGenerator(PetkoparaFormGenerator $formGenerator)
    {
        $this->formGenerator = $formGenerator;
    }

}
