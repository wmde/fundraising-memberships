<?php

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Constraint\IsAnything;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use RuntimeException;

trait ThrowingEntityManagerTrait {

	public function getThrowingEntityManager(): EntityManager {
		$entityManager = $this->getMockBuilder( EntityManager::class )
			->disableOriginalConstructor()->getMock();

		$entityManager->expects( $this->any() )
			->method( $this->anything() )
			->willThrowException( new class( 'This is a test exception from ' . self::class )
				extends RuntimeException implements ORMException {
			} );

		return $entityManager;
	}

	abstract protected function getMockBuilder( string $className ): MockBuilder;

	abstract protected function any(): AnyInvokedCountMatcher;

	abstract public static function anything(): IsAnything;
}
