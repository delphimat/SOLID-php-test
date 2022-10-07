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

class customAction extends Action
{
    protected function getVoyages(Ship $ship): array
    {
        // TODO: Implement getVoyages() method.
    }

    protected function getCsvWriter(): CsvWriter
    {
        // TODO: Implement getCsvWriter() method.
    }
}

call_action();
call_action();

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
