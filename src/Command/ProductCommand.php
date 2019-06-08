<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Product;
use Pimple\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductCommand extends Command
{
    protected $container;

    const COUNT_MIN = 1;
    const COUNT_MAX = 100;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('product:create')
            ->setDescription('Fills database with products')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'How many products to create', 20)
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $input->getOption('count');

        if ($count < self::COUNT_MIN || $count > self::COUNT_MAX) {
            $output->writeln(sprintf('Count should by between %d and %d', self::COUNT_MIN, self::COUNT_MAX));
            exit;
        }

        $em = $this->container['em'];

        for ($i = 1; $i <= $count; $i++) {
            $product = (new Product())
                ->setName(sprintf('Товар %d', $i))
                ->setPrice(((float) mt_rand(10000, 9999999)) / 100);
            $em->persist($product);
        }

        $em->flush();
    }
}
