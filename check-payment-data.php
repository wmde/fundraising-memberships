<?php

// A script to test data quality in donations for migration to the new payments

use Doctrine\DBAL\DriverManager;
use WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration\MembershipToPaymentConverter;
use WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration\ResultObject;
use WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration\SequentialPaymentIdGenerator;

require __DIR__ . '/vendor/autoload.php';

$config = [
	'url' => 'mysql://fundraising:INSECURE PASSWORD@database/fundraising'
];


$db = DriverManager::getConnection( $config );
$converter = new MembershipToPaymentConverter( $db );

$result = $converter->convertMemberships();

$errors = $result->getErrors();
$warnings = $result->getWarnings();
$processedPayments = $result->getMembershipCount();
$errorCount = array_reduce($errors, fn(int $acc, ResultObject $error) => $acc + $error->getItemCount(), 0 );
$warningCount = array_reduce($warnings, fn(int $acc, ResultObject $error) => $acc + $error->getItemCount(), 0 );


printf( "\nProcessed %d memberships, with %d errors (%.2f%%) and %d warnings (%.2f%%)\n",
	$processedPayments,
	$errorCount,
	( $errorCount * 100 ) / $processedPayments,
	$warningCount,
	( $warningCount * 100 ) / $processedPayments
);

echo "\nWarnings\n";
echo "--------\n";
printf("|Error|Memberships affected|Date Range|\n");
foreach($warnings as $type => $warning) {
	$dateRange = $warning->getDateRange();
	$lower = new DateTimeImmutable($dateRange->getLowerBound());
	$upper = new DateTimeImmutable($dateRange->getUpperBound());
	$warningCount = $warning->getItemCount();
	$percentageOfDonations = ( $warningCount * 100 ) / $processedPayments;
	printf("|%-60s: | %d (%.2f%%) | (%s - %s) |\n", $type, $warningCount, $percentageOfDonations, $lower->format('Y-m-d'), $upper->format('Y-m-d') );
}

if ( count($errors) === 0) {
	echo "\nNo errors.\n";
	return;
}

echo "\nErrors\n";
echo "------\n";
foreach($errors as $type => $error) {
	printf("%s: %d\n", $type, $error->getItemCount());
}
/** @var ResultObject $lastErrorResult */
$lastErrorResult = reset($errors);
$lastErrorClass = key($errors);
echo "$lastErrorClass\n";
print_r($lastErrorResult->getItemSample());


