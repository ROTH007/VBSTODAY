<?php

namespace App\Services;

use App\Exceptions\InvalidCompanyHeaderException;
use App\Imports\AdiImport;
use App\Imports\CfoImport;
use App\Imports\TctImport;
use App\Imports\TelecomImport;
use App\Models\Builder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Matches an uploaded company file against the Builder rows for that
 * company's infrastructure, by job number. Replaces the per-company
 * blocks that used to live in ImportFileController::verifyData().
 */
class ImportMatcher
{
    public const TITLES = [
        'cfocn' => 'CFOCN',
        'tct' => 'TCT',
        'adi' => 'ADI',
        'telecom' => 'Telecom',
    ];

    public const COMPANY_HEADERS = [
        'cfocn' => ['Work Order', 'PORT', 'POS', 'Team Install', 'Create Time'],
        'tct' => ['TCT Id', 'TCT SID', 'New circuit ID', 'Status', 'Total NRC'],
        'adi' => ['AID', 'SID', 'Customer Name', 'Qty', 'Amount', 'VAT', 'Total Amount', 'Invoice Description', 'Date Start', 'Date To'],
        'telecom' => ['Account No', 'ID', 'Name Customer', 'Project', 'Issue Date', 'Complete Date'],
    ];

    /**
     * The column in each company's mapped row that holds the job number
     * to compare against the Builder file's 'jobs' column.
     */
    private const JOB_KEY = [
        'cfocn' => 'Work Order',
        'tct' => 'New circuit ID',
        'adi' => 'AID',
        'telecom' => 'ID',
    ];

    /**
     * @param  string  $company  one of cfocn|tct|adi|telecom
     * @param  array  $headerRow  $validateCompanyHeader[0][0] from HeadingRowImport
     * @return array{match: array, not_match: array, bill: array, company_header: array}
     *
     * @throws InvalidCompanyHeaderException
     */
    public function match(string $company, array $headerRow, UploadedFile $companyFile): array
    {
        if (! isset(self::TITLES[$company])) {
            throw new \InvalidArgumentException("Unknown company: {$company}");
        }

        if (! $this->headerIsValid($company, $headerRow)) {
            throw new InvalidCompanyHeaderException($company);
        }

        $title = self::TITLES[$company];
        $jobKey = self::JOB_KEY[$company];

        $builderRows = Builder::where('infrastructure', 'LIKE', '%'.$title.'%')->get()->toArray();

        $jobsMap = [];
        foreach ($builderRows as $builderRow) {
            if (! empty($builderRow['jobs'])) {
                $jobsMap[$builderRow['jobs']] = true;
            }
        }

        $companyRows = $this->loadCompanyRows($company, $companyFile);

        // Business rule: a row with Status 'N' doesn't count toward match,
        // not-match or bill at all. Only TCT has a Status column today.
        if ($company === 'tct') {
            $companyRows = array_filter($companyRows, fn ($row) => ($row['Status'] ?? null) !== 'N');
        }

        $match = [];
        $notMatch = $companyRows;
        $matchedJobs = [];

        foreach ($companyRows as $key => $row) {
            $jobNumber = $row[$jobKey] ?? null;
            if ($jobNumber !== null && isset($jobsMap[$jobNumber])) {
                $match[] = $row;
                $matchedJobs[$jobNumber] = true;
                unset($notMatch[$key]);
            }
        }

        $bill = $builderRows;
        foreach ($bill as $idx => $builderRow) {
            if (isset($matchedJobs[$builderRow['jobs']])) {
                unset($bill[$idx]);
            }
        }

        return [
            'match' => array_values($match),
            'not_match' => array_values($notMatch),
            'bill' => array_values($bill),
            'company_header' => self::COMPANY_HEADERS[$company],
        ];
    }

    private function headerIsValid(string $company, array $headerRow): bool
    {
        return match ($company) {
            'cfocn' => ($headerRow[0] ?? null) === 'work_order'
                && ($headerRow[1] ?? null) === 'port'
                && ($headerRow[2] ?? null) === 'pos'
                && ($headerRow[3] ?? null) === 'team_install'
                && ($headerRow[4] ?? null) === 'create_time',
            'tct' => ($headerRow[1] ?? null) === 'tct_cid'
                && ($headerRow[2] ?? null) === 'tct_sid'
                && ($headerRow[3] ?? null) === 'new_circuit_id'
                && ($headerRow[18] ?? null) === 'total_nrc',
            'adi' => ($headerRow[1] ?? null) === 'aid'
                && ($headerRow[2] ?? null) === 'sid'
                && ($headerRow[3] ?? null) === 'customer_name'
                && ($headerRow[4] ?? null) === 'qty'
                && ($headerRow[5] ?? null) === 'date_start'
                && ($headerRow[6] ?? null) === 'date_to'
                && ($headerRow[7] ?? null) === 'amount'
                && ($headerRow[8] ?? null) === 'vat'
                && ($headerRow[9] ?? null) === 'total_amount'
                && ($headerRow[10] ?? null) === 'invoice_description',
            'telecom' => ($headerRow[1] ?? null) === 'account_no'
                && ($headerRow[2] ?? null) === 'id'
                && ($headerRow[3] ?? null) === 'name_customer'
                && ($headerRow[5] ?? null) === 'project'
                && ($headerRow[6] ?? null) === 'issue_date'
                && ($headerRow[7] ?? null) === 'complete_date',
            default => false,
        };
    }

    private function loadCompanyRows(string $company, UploadedFile $companyFile): array
    {
        $storeAsArray = match ($company) {
            'cfocn' => Excel::toArray(new CfoImport(), $companyFile),
            'tct' => Excel::toArray(new TctImport(), $companyFile),
            'adi' => Excel::toArray(new AdiImport(), $companyFile),
            'telecom' => Excel::toArray(new TelecomImport(), $companyFile),
        };

        return $storeAsArray[0];
    }
}
