<?php

declare(strict_types=1);

// This is a PHP 7.0 example.

// Scenario: Imagine a web site that shows information about cruise voyages.
// Cruise voyages are operated by cruise line companies on ships owned by those same companies.
// They generally offer APIs to travel agencies and similar partners.
//
// For example, to show those voyages on a website, they'd have to be retrieved from such an API.
// A voyage API from a cruise line might, for example, list an id, a start date and an end date for
// all voyages of a specific ship.
//
// This task reflects this scenario - you'd use the API to retrieve and
// write voyage data into a csv file.

// Todo: Use the call_action function twice in order to
//
// a) create a voyages.csv with the following content:
// voyageId;startDate;endDate;duration
// 12567;2017-01-01;2017-02-01;31
// 16742;2017-05-23;2017-05-30;6
//
// b) Throw a RuntimeException with the message: 'Ship not found!'

/**
 *
 */
class CustomAction extends Action
{
    /**
     * @var CustomCsvWriter
     */
    protected $customCsvWriter;

    public function __construct()
    {
        $this->customCsvWriter = new CustomCsvWriter();
    }

    /**
     * @param Ship $ship
     * @return array|Voyage[]
     */
    protected function getVoyages(Ship $ship): array
    {
        $voyageShips = (new CustomShip($ship->getName()))->getCruiseLine()->getVoyagesForShip($ship);
        $voyages = [];

        foreach ($voyageShips as $voyageShip) {
            if (is_array($voyageShip) && CustomVoyage::isValid($voyageShip)) {
                $voyages[] = new CustomVoyage($voyageShip);
            }
        }

        return $voyages;
    }

    /**
     * @return CsvWriter
     */
    protected function getCsvWriter(): CsvWriter
    {
        return $this->customCsvWriter;
    }
}

/**
 * CustomVoyage is model to save the information of a voyage
 */
class CustomVoyage implements Voyage
{
    /**
     * @var int
     */
    protected $voyageId;
    /**
     * @var DateTime
     */
    protected $startTime;
    /**
     * @var DateTime
     */
    protected $endtime;
    /**
     * @var string
     */
    protected $duration;

    /**
     * @param array $row
     * @return bool
     */
    static public function isValid(array $row): bool
    {
        return isset($row['voyageId']) && isset($row['startTime']) && isset($row['endTime']);
    }

    /**
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->voyageId = $row['voyageId'];
        $this->startTime = (new DateTime())->setTimestamp($row['startTime']);
        $this->endtime = (new DateTime())->setTimestamp($row['endTime']);
        $this->duration = $this->startTime->diff($this->endtime)->format('%a');
    }

    /**
     * @return int
     */
    public function getVoyageId(): int
    {
        return $this->voyageId;
    }

    /**
     * @return int
     */
    public function getStartTime(): string
    {
        return $this->startTime->format("Y-m-d");
    }

    /**
     * @return int
     */
    public function getEndtime(): string
    {
        return $this->endtime->format("Y-m-d");
    }

    /**
     * @return string
     */
    public function getDuration(): string
    {
        return $this->duration;
    }

    /**
     * @return array
     */
    public function getRowCSV(): array
    {
        return [
            $this->getVoyageId(),
            $this->getStartTime(),
            $this->getEndtime(),
            $this->getDuration()
        ];
    }
}

/**
 * CustomCsvWriter is a class to generate csv from an array of Voyage
 */
class CustomCsvWriter implements CsvWriter
{
    /**
     * Header CSV
     */
    const HEADER_CSV = [
        'voyageId',
        'startDate',
        'endDate',
        'duration'
    ];

    /**
     * @param string $filename
     * @param array $voyages
     * @return void
     */
    public function writeVoyagesToCsv(string $filename, array $voyages)
    {
        if (!file_exists(Action::FILENAME)) {
            touch(Action::FILENAME);
        }

        $fp = fopen(Action::FILENAME, 'w');
        fputcsv($fp, self::HEADER_CSV, ',');

        foreach ($voyages as $voyage) {

            if ($voyage instanceof Voyage) {
                fputcsv($fp, $voyage->getRowCSV(), ',');
            }

        }

        fclose($fp);
    }
}

/**
 * Class used to get all the traverls from the ship
 */
class CustomCruiseLine implements CruiseLine
{
    /**
     * @var CruiseLineApi
     */
    protected $cruiseLineApi;

    /**
     *
     */
    public function __construct()
    {
        $this->cruiseLineApi = new CruiseLineApi();
    }

    /**
     * @param Ship $ship
     * @return int[][]|string[]
     */
    public function getVoyagesForShip(Ship $ship): array
    {
        try {
            $datas = $this->cruiseLineApi->getVoyages($ship->getName());
        } catch (Exception $exception) {
            // @todo log this exception
            throw new RuntimeException(sprintf("Error happened with the CruiselineApi: %s", $exception->getMessage()));
        }

        if (isset($datas['error'])) {
            throw new RuntimeException("Ship not found!");
        }

        return $datas;
    }
}

/**
 * Class model to save the information of the ship
 * name, etc...
 */
class CustomShip implements Ship
{
    /**
     * @var
     */
    protected $nameShip;
    /**
     * @var CustomCruiseLine
     */
    private $customCruiseLine;

    /**
     * @param $nameShip
     */
    public function __construct($nameShip)
    {
        $this->nameShip = $nameShip;
        $this->customCruiseLine = new CustomCruiseLine();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->nameShip;
    }

    /**
     * @return CruiseLine
     */
    public function getCruiseLine(): CruiseLine
    {
        return $this->customCruiseLine;
    }
}

call_action(new customAction(), new customShip('AIDAaura'));
call_action(new customAction(), new customShip('Marco'));

// Do _not_ modify anything beyond this point.

function call_action(Action $action, Ship $ship)
{
    $action->call($ship);
}


abstract class Action
{
    const FILENAME = __DIR__ . '/voyages.csv';

    final public function call(Ship $ship)
    {
        $voyages = $this->getVoyages($ship);
        foreach ($voyages as $voyage) {
            if (!$voyage instanceof Voyage) {
                throw new RuntimeException("This isn't supposed to happen.");
            }
        }

        $this->getCsvWriter()->writeVoyagesToCsv(self::FILENAME, $voyages);
    }

    /**
     * @return Voyage[]
     */
    abstract protected function getVoyages(Ship $ship): array;

    abstract protected function getCsvWriter(): CsvWriter;
}

/**
 *
 */
class CruiseLineApi
{
    public function getVoyages(string $shipName): array
    {
        // This is a simplified "cached" call and would actually request data from a web service
        if ($shipName === 'AIDAaura') {
            return [
                [
                    'voyageId' => 12567,
                    'startTime' => 1483272900,
                    'endTime' => 1485965713,
                ],
                [
                    'voyageId' => 16742,
                    'startTime' => 1495555200,
                    'endTime' => 1496138400,
                ],
            ];
        }

        return ['error' => 'Ship not found!'];
    }
}

interface CruiseLine
{
    public function getVoyagesForShip(Ship $ship): array;
}

interface Ship
{
    public function getName(): string;

    public function getCruiseLine(): CruiseLine;
}

interface Voyage
{
}

interface CsvWriter
{
    /**
     * @param string $filename
     * @param Voyage[] $voyages
     */
    public function writeVoyagesToCsv(string $filename, array $voyages);
}
