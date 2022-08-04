<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use WMDE\Fundraising\PaymentContext\PaymentContextFactory;

/**
 * Command line Migration script for inserting payments from memberships
 *
 * We can't use Doctrine migrations because we don't want to rely on the transaction configuration of
 * Doctrine migrations and this script needs transactions to run in a reasonable amount of time
 *
 * This can be deleted when the payment data has been migrated successfully in production
 */
class PaymentMigrationCommand {
	public static function run(): void {
		$db = ConnectionFactory::getConnection();
		$entityManager = self::getEntityManager( $db );

		$paymentIdCollection = new MembershipPaymentIdCollection();
		$paymentHandler = new InsertingPaymentHandler( $entityManager, $paymentIdCollection );
		$converter = new MembershipToPaymentConverter( $db, $paymentHandler );

		$conversionStart = microtime( true );
		$result = $converter->convertMemberships( self::getStartingMembershipId( $db ), MembershipToPaymentConverter::CONVERT_ALL );
		$paymentHandler->flushRemaining();
		$conversionEnd = microtime( true );

		printf( "\nTook %d seconds to convert %d memberships\n", $conversionEnd - $conversionStart, $result->getMembershipCount() );

		$errors = $result->getErrors();
		if ( count( $errors ) > 0 ) {
			echo "\nThere were errors during the data migration!\n";
			foreach ( $errors as $type => $error ) {
				printf( "%s: %d\n", $type, $error->getItemCount() );
			}
			exit( 1 );
		}

		$unassignedPayments = $db->fetchFirstColumn( "SELECT id FROM request WHERE payment_id IS NULL" );
		if ( count( $unassignedPayments ) > 0 ) {
			echo "The following memberships have unassigned payment IDs:\n";
			echo implode( "\n", $unassignedPayments );
			exit( 1 );
		}
	}

	private static function getStartingMembershipId( Connection $db ): int {
		// subtract 1 because starting ID is exclusive
		$minId = intval( $db->fetchOne( "SELECT MIN(id) FROM request" ) ) - 1;
		// return 0 when minId is -1 (meaning there were no rows)
		return max( 0, $minId );
	}

	private static function getEntityManager( Connection $db ): EntityManager {
		$paymentContextFactory = new PaymentContextFactory();
		$paymentContextFactory->registerCustomTypes( $db );
		$ormConfig = ORMSetup::createXMLMetadataConfiguration( $paymentContextFactory->getDoctrineMappingPaths() );
		return EntityManager::create( $db, $ormConfig );
	}
}
