<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Investment;
use App\Repository\InvestmentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

class WithdrawInvestmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;   
    private InvestmentRepository $investmentRepository;
    
    public function __construct(InvestmentRepository $investmentRepository, EntityManagerInterface $entityManager)
    {
        $this->investmentRepository = $investmentRepository;
        $this->entityManager = $entityManager;
    }  

    #[Route('/api/investment/{investmentId}/withdraw', methods: "PUT")]
    public function withdrawInvestment(int $investmentId)
    {
        $investment = $this->getInvestmentById($investmentId);
        
        if (!$investment) {            
            return $this->json(['error' => 'Investment not found'], 404);
        }

        // Lógica para calcular impostos e realizar retirada
        $withdrawResult = $this->calculateWithdrawal($investment);

        if ($withdrawResult['success']) {
            $this->entityManager->flush();
            return $this->json(['message' => 'Investment withdrawn successfully', 'withdrawal_amount' => $withdrawResult['withdrawal_amount']]);
        } else {
            return $this->json(['error' => $withdrawResult['message']], 400);
        }
    }    

    private function calculateWithdrawal(Investment $investment)
{
    $currentBalance = $investment->getBalance();
    $initialValue = $investment->getValue();

    // Calcular os dias totais de investimento
    $currentDate = new \DateTime();
    $startDate = $investment->getCreatedAt();
    $daysInvested = $startDate->diff($currentDate)->days;

    // Calcular os juros mensais
    $monthlyInterestRate = 0.0052;  // Taxa de juros mensal (ajuste conforme necessário)
    $earnedInterest = $initialValue * pow(1 + $monthlyInterestRate, $daysInvested / 30) - $initialValue;

    if ($earnedInterest <= 0) {
        return ['success' => false, 'message' => 'Insufficient funds for withdrawal'];
    }

    $age = (new \DateTime())->diff($investment->getCreatedAt())->y;
    $taxRate = $this->calculateTaxRate($age);

    // Calcular o valor total antes dos descontos
    $totalAmount = $currentBalance + $earnedInterest;

    // Lógica corrigida para calcular o valor do saque após impostos
    $withdrawAmount = $totalAmount - ($earnedInterest * $taxRate);

    if ($withdrawAmount <= 0) {
        return ['success' => false, 'message' => 'Insufficient funds for withdrawal after taxes'];
    }

    $investment->setEarnings($investment->getEarnings() + $earnedInterest);

    return [
        'success' => true,
        'total_amount' => $totalAmount,
        'withdrawal_amount' => $withdrawAmount,
    ];
}


    private function calculateTaxRate($age)
    {
        // Lógica para calcular a taxa de imposto com base na idade do investimento
        if ($age < 1) {
            return 0.225;  // 22.5%
        } elseif ($age < 2) {
            return 0.185;  // 18.5%
        } else {
            return 0.15;   // 15%
        }
    }

    private function calculateInvestmentDetails(Investment $investment, $gain, $taxRate)
    {
        $currentBalance = $investment->getBalance();
        $currentDate = new DateTime();
        $startDate = $investment->getCreatedAt();

        $daysInvested = $startDate->diff($currentDate)->days;  // Correção para considerar dias
        $monthlyInterestRate = 0.0052;  // Taxa de juros mensal (exemplo, ajuste conforme necessário)
        $earnedInterest = $investment->getValue() * pow(1 + $monthlyInterestRate, $daysInvested / 30); // Convertemos dias para meses (aproximadamente)

        $withdrawAmount = $currentBalance - ($gain * $taxRate);

        return [
            'id' => $investment->getId(),
            'name' => $investment->getOwner()->getName(),
            'amount' => $investment->getValue(),
            'earnings' => $earnedInterest,
            'expected_balance' => $investment->getValue() + $earnedInterest,
            'withdrawal_amount' => $withdrawAmount,
            'days_invested' => $daysInvested,
        ];
    }

    private function getInvestmentById(int $investmentId)
    {
        return $this->investmentRepository->find($investmentId);
    }
}

