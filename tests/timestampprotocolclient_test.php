<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the TimeStampProtocolClient class
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

/**
 * Tests for the TimeStampProtocolClient class
 */
final class timestampprotocolclient_test extends \advanced_testcase {

    /**
     * Tests the creation of a TimeStampProtocolClient instance
     *
     * @covers \quiz_archiver\TimeStampProtocolClient::__construct
     * @covers \quiz_archiver\TimeStampProtocolClient::get_serverurl
     *
     * @return void
     */
    public function test_creation(): void {
        $client = new TimeStampProtocolClient('http://localhost:12345');
        $this->assertInstanceOf(TimeStampProtocolClient::class, $client);
        $this->assertEquals('http://localhost:12345', $client->get_serverurl());
    }

    /**
     * Tests the generation of a nonce
     *
     * @covers \quiz_archiver\TimeStampProtocolClient::generate_nonce
     *
     * @return void
     * @throws \Exception
     */
    public function test_generate_nonce(): void {
        $nonce = TimeStampProtocolClient::generate_nonce();
        $this->assertNotEmpty($nonce, 'Nonce is empty');
        $this->assertSame(16, strlen($nonce), 'Nonce length is not 16 bytes');

        for ($i = 0; $i < 100; $i++) {
            $this->assertNotEquals(
                $nonce,
                TimeStampProtocolClient::generate_nonce(),
                'Repeated calls to generate_nonce() return the same nonce'
            );
        }
    }

    /**
     * Tests the generation of a TSP request from valid data
     *
     * @covers \quiz_archiver\TimeStampProtocolClient::sign
     * @covers \quiz_archiver\TimeStampProtocolClient::create_timestamp_request
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_signing_valid_data(): void {
        $client = new TimeStampProtocolClient('http://localhost:12345');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/'.get_string('tsp_client_error_curl', 'quiz_archiver', '').'/');
        $client->sign('6e82908cfa04dbf1706aa938e32f27e6a1d5f096df5c472795a93f8ab9de4c72');
    }

    /**
     * Test the generation of a TSP request from invalid data
     *
     * @covers \quiz_archiver\TimeStampProtocolClient::sign
     *
     * @return void
     * @throws \Exception
     */
    public function test_signing_invalid_data(): void {
        $client = new TimeStampProtocolClient('http://localhost:12345');

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessageMatches('/Invalid hexadecimal SHA256 hash/');
        $client->sign('invalid-data');
    }

}
