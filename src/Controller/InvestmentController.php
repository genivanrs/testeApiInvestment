<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Investment;
use App\Entity\Person;
use App\Repository\InvestmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

class InvestmentController extends AbstractController

{
    private EntityManagerInterface $entityManager;   

    public function __construct(InvestmentRepository $investmentRepository, EntityManagerInterface $entityManager){
        $this->investmentRepository=$investmentRepository;
        $this->entityManager=$entityManager;
    }   

    #[Route('/api/investment', methods:"POST")]
public function createInvestment(Request $request)
{        
    $requestData = $request->toArray();

    // Verificar se a chave 'name' existe no array
    $name = isset($requestData['name']) ? $requestData['name'] : null;

    // Verificar se a chave 'value' existe no array
    $value = isset($requestData['value']) ? $requestData['value'] : null;

    // Verificar se ambas as chaves estão presentes
    if ($name === null || $value === null) {
        return $this->json(['error' => 'Invalid data. Both "name" and "value" are required.'], 400);
    }

    $person = new Person();
    $person->setName($name);

    $investment = new Investment();
    $investment->setValue($value);

    // Ajuste a data de criação para simular um investimento de 1 ano atrás
    $createdAt = new \DateTimeImmutable('-1 year');
    $investment->setCreatedAt($createdAt);    
    
    $investment->setOwner($person);

    $this->entityManager->persist($person);
    $this->entityManager->persist($investment);
    $this->entityManager->flush();

    return $this->json(['message' => 'Investment created successfully']);
}

    private function getInvestmentById(int $investmentId)
    {
        return $this->entityManager->getRepository(Investment::class)->find($investmentId);
    }
}
