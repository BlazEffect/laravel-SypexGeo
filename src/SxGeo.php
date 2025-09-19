<?php

namespace Scriptixru\SypexGeo;

/***************************************************************************\
| Sypex Geo                  version 2.2.3                                  |
| (c)2006-2014 zapimir       zapimir@zapimir.net       http://sypex.net/    |
| (c)2006-2014 BINOVATOR     info@sypex.net                                 |
|---------------------------------------------------------------------------|
|     created: 2006.10.17 18:33              modified: 2014.06.20 18:57     |
|---------------------------------------------------------------------------|
| Sypex Geo is released under the terms of the BSD license                  |
|   http://sypex.net/bsd_license.txt                                        |
\***************************************************************************/

use RuntimeException;

const SXGEO_FILE = 0;
const SXGEO_MEMORY = 1;
const SXGEO_BATCH = 2;

class SxGeo
{
    private $fh;
    private array $info = [];
    private int $range;
    private int $db_begin;
    private ?string $b_idx_str = null;
    private ?string $m_idx_str = null;
    private ?array $b_idx_arr = null;
    private ?array $m_idx_arr = null;
    private int $m_idx_len;
    private int $b_idx_len;
    private int $db_items;
    private int $country_size;
    private ?string $db = null;
    private ?string $regions_db = null;
    private ?string $cities_db = null;
    private ?string $default_iso_country_code = null;
    private array $ignored_ip = [];

    private int $id_len;
    private int $block_len;
    private int $max_region;
    private int $max_city;
    private int $max_country;
    private array $pack = [];

    public array $id2iso = [
        '', 'AP', 'EU', 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'CW', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU',
        'AW', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BM', 'BN', 'BO', 'BR', 'BS',
        'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG',
        'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'SX', 'GA', 'GB', 'GD', 'GE', 'GF',
        'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN',
        'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JM', 'JO', 'JP', 'KE',
        'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR',
        'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP',
        'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI',
        'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN',
        'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG',
        'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'ST', 'SV', 'SY', 'SZ', 'TC', 'TD', 'TF',
        'TG', 'TH', 'TJ', 'TK', 'TM', 'TN', 'TO', 'TL', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM',
        'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'RS', 'ZA',
        'ZM', 'ME', 'ZW', 'A1', 'XK', 'O1', 'AX', 'GG', 'IM', 'JE', 'BL', 'MF', 'BQ', 'SS'
    ];

    public bool $batch_mode = false;
    public bool $memory_mode = false;

    /**
     * @param string $db_file
     * @param int $type
     *
     * @throws RuntimeException
     */
    public function __construct(string $db_file = 'SxGeoCityMax.dat', int $type = SXGEO_FILE)
    {
        // Сначала убеждаемся, что есть файл базы данных
        $this->fh = @fopen($db_file, 'rb');
        if (!$this->fh) {
            throw new RuntimeException("Can't open {$db_file}");
        }

        $header = fread($this->fh, 40); // В версии 2.2 заголовок увеличился на 8 байт
        if ($header === false || !str_starts_with($header, 'SxG')) {
            throw new RuntimeException("Wrong file format {$db_file}");
        }

        $info = unpack(
            'Cver/Ntime/Ctype/Ccharset/Cb_idx_len/nm_idx_len/nrange/Ndb_items/Cid_len/nmax_region/nmax_city/Nregion_size/Ncity_size/nmax_country/Ncountry_size/npack_size',
            substr($header, 3)
        );

        if (!is_array($info) || ($info['b_idx_len'] * $info['m_idx_len'] * $info['range'] * $info['db_items'] * $info['time'] * $info['id_len']) === 0) {
            throw new RuntimeException("Corrupted SypexGeo file {$db_file}");
        }

        $this->range = (int)$info['range'];
        $this->b_idx_len = $info['b_idx_len'];
        $this->m_idx_len = (int)$info['m_idx_len'];
        $this->db_items = (int)$info['db_items'];
        $this->id_len = (int)$info['id_len'];
        $this->block_len = 3 + $this->id_len;
        $this->max_region = (int)$info['max_region'];
        $this->max_city = (int)$info['max_city'];
        $this->max_country = (int)$info['max_country'];
        $this->country_size = (int)$info['country_size'];

        $this->batch_mode = (bool)($type & SXGEO_BATCH);
        $this->memory_mode = (bool)($type & SXGEO_MEMORY);

        $this->pack = $info['pack_size'] ? explode("\0", fread($this->fh, (int)$info['pack_size'])) : [];
        $this->b_idx_str = fread($this->fh, (int)$info['b_idx_len'] * 4);
        $this->m_idx_str = fread($this->fh, (int)$info['m_idx_len'] * 4);
        $this->db_begin = ftell($this->fh);

        if ($this->batch_mode) {
            $this->b_idx_arr = array_values(unpack("N*", $this->b_idx_str));
            $this->m_idx_arr = str_split($this->m_idx_str, 4);
        }

        if ($this->memory_mode) {
            $this->db = fread($this->fh, $this->db_items * $this->block_len);
            $this->regions_db = $info['region_size'] > 0 ? fread($this->fh, (int)$info['region_size']) : '';
            $this->cities_db = $info['city_size'] > 0 ? fread($this->fh, (int)$info['city_size']) : '';
        }

        $this->info = $info;
        $this->info['regions_begin'] = $this->db_begin + $this->db_items * $this->block_len;
        $this->info['cities_begin'] = $this->info['regions_begin'] + (int)$info['region_size'];
    }

    /**
     * Возвращает число/seek для переданного IP или false при ошибке.
     */
    public function get_num(string $ip): int|false
    {
        $parts = explode('.', $ip, 2);
        $ip1n = (int)($parts[0] ?? 0);

        if ($ip1n === 0 || $ip1n === 10 || $ip1n === 127 || $ip1n >= $this->b_idx_len) {
            return false;
        }

        $ipn_long = ip2long($ip);
        if ($ipn_long === false) {
            return false;
        }

        $ipn = pack('N', $ipn_long);

        // Находим блок данных в индексе первых байт
        if ($this->batch_mode) {
            $blocks = ['min' => (int)$this->b_idx_arr[$ip1n - 1], 'max' => (int)$this->b_idx_arr[$ip1n]];
        } else {
            $slice = substr($this->b_idx_str, ($ip1n - 1) * 4, 8);
            $blocks = unpack('Nmin/Nmax', $slice);
        }

        if (($blocks['max'] - $blocks['min']) > $this->range) {
            // Ищем блок в основном индексе
            $part = $this->search_idx($ipn, (int)floor($blocks['min'] / $this->range), (int)floor($blocks['max'] / $this->range) - 1);
            // Нашли номер блока в котором нужно искать IP, теперь находим нужный блок в БД
            $min = $part > 0 ? $part * $this->range : 0;
            $max = $part > $this->m_idx_len ? $this->db_items : ($part + 1) * $this->range;

            // Нужно проверить чтобы блок не выходил за пределы блока первого байта
            if ($min < $blocks['min']) $min = $blocks['min'];
            if ($max > $blocks['max']) $max = $blocks['max'];
        } else {
            $min = (int)$blocks['min'];
            $max = (int)$blocks['max'];
        }

        $len = $max - $min;
        if ($len <= 0) {
            return false;
        }

        // Находим нужный диапазон в БД
        if ($this->memory_mode) {
            return $this->search_db($this->db, $ipn, $min, $max);
        }

        fseek($this->fh, $this->db_begin + $min * $this->block_len);
        $buf = fread($this->fh, $len * $this->block_len);
        return $this->search_db($buf, $ipn, 0, $len);
    }

    public function setDefaultIsoCountryCode(string $code): void
    {
        $this->default_iso_country_code = $code;
    }

    public function setIgnoredIp(array $ip_list): void
    {
        $this->ignored_ip = $ip_list;
    }

    /**
     * Главный геттер: возвращает либо city array, либо country ISO string.
     * Если max_city == 0 — возвращает только код страны.
     */
    public function get(string $ip): array|string|false|null
    {
        return $this->max_city ? $this->getCity($ip) : $this->getCountry($ip);
    }

    public function getCountry(string $ip): ?string
    {
        if ($this->max_city) {
            $tmp = $this->parseCity($this->get_num($ip));
            return $tmp['country']['iso'] ?? $this->default_iso_country_code;
        }

        if (in_array($ip, $this->ignored_ip, true)) {
            return $this->default_iso_country_code;
        }

        $num = $this->get_num($ip);
        if ($num === false || $num === 0) {
            return $this->default_iso_country_code;
        }

        return $this->id2iso[$num] ?? $this->default_iso_country_code;
    }

    public function getCountryId(string $ip): int|false|null
    {
        if ($this->max_city) {
            $tmp = $this->parseCity($this->get_num($ip));
            return $tmp['country']['id'] ?? null;
        }

        return $this->get_num($ip) ?: null;
    }

    public function getCity(string $ip): array|false
    {
        $seek = $this->get_num($ip);
        return $seek ? $this->parseCity($seek) : false;
    }

    public function getCityFull(string $ip): array|false
    {
        $seek = $this->get_num($ip);
        return $seek ? $this->parseCity($seek, true) : false;
    }

    public function about(): array
    {
        $charsets = ['utf-8', 'latin1', 'cp1251'];
        $types = [
            'n/a',
            'SxGeo Country',
            'SxGeo City RU',
            'SxGeo City EN',
            'SxGeo City',
            'SxGeo City Max RU',
            'SxGeo City Max EN',
            'SxGeo City Max',
        ];

        return [
            'Created' => date('Y.m.d', $this->info['time']),
            'Timestamp' => $this->info['time'],
            'Charset' => $charsets[$this->info['charset']] ?? 'unknown',
            'Type' => $types[$this->info['type']] ?? 'undefined',
            'Byte Index' => $this->b_idx_len,
            'Main Index' => $this->m_idx_len,
            'Blocks In Index Item' => $this->range,
            'IP Blocks' => $this->db_items,
            'Block Size' => $this->block_len,
            'City' => [
                'Max Length' => $this->max_city,
                'Total Size' => $this->info['city_size'],
            ],
            'Region' => [
                'Max Length' => $this->max_region,
                'Total Size' => $this->info['region_size'],
            ],
            'Country' => [
                'Max Length' => $this->max_country,
                'Total Size' => $this->info['country_size'],
            ],
        ];
    }

    protected function search_idx(string $ipn, int $min, int $max): int
    {
        if ($this->batch_mode) {
            while ($max - $min > 8) {
                $offset = ($min + $max) >> 1;
                if ($ipn > $this->m_idx_arr[$offset]) {
                    $min = $offset;
                } else {
                    $max = $offset;
                }
            }
            while ($min < $max && $ipn > $this->m_idx_arr[$min]) {
                $min++;
            }
        } else {
            while ($max - $min > 8) {
                $offset = ($min + $max) >> 1;
                $val = substr($this->m_idx_str, $offset * 4, 4);
                if ($ipn > $val) {
                    $min = $offset;
                } else {
                    $max = $offset;
                }
            }
            while ($min < $max && $ipn > substr($this->m_idx_str, $min * 4, 4)) {
                $min++;
            }
        }
        return $min;
    }

    /**
     * Поиск в самой БД. $str — бинарная строка с запрошенной частью БД.
     * $ipn — 4-байтный big-endian (pack('N', ip2long($ip))).
     * Возвращает числовой id (country/city id) или 0/false.
     */
    protected function search_db(string $str, string $ipn, int $min, int $max): int|false
    {
        // Когда в блоке >1 записей, сравниваем только последние 3 байта (диапазон 3 байта)
        if ($max - $min > 1) {
            $ipn3 = substr($ipn, 1); // последние 3 байта
            while ($max - $min > 8) {
                $offset = ($min + $max) >> 1;
                $blockPrefix = substr($str, $offset * $this->block_len, 3);
                if ($ipn3 > $blockPrefix) {
                    $min = $offset;
                } else {
                    $max = $offset;
                }
            }
            while ($min + 1 < $max && $ipn3 >= substr($str, $min * $this->block_len, 3)) {
                $min++;
            }
        } else {
            $min++;
        }

        $pos = $min * $this->block_len - $this->id_len;
        if ($pos < 0) {
            return false;
        }
        $idBytes = substr($str, $pos, $this->id_len);
        if ($idBytes === '') {
            return false;
        }

        return (int)hexdec(bin2hex($idBytes));
    }

    /**
     * Читает структуру регионов/городов из файла или из памяти и распаковывает через unpack()
     * $type: 1 — regions, 2 — cities, 0 — country (pack index mapping preserved from оригинала)
     */
    protected function readData(int $seek, int $max, int $type): array|false
    {
        if (!$seek || !$max) {
            return $this->unpack($this->pack[$type] ?? '', '');
        }

        if ($this->memory_mode) {
            $raw = substr($type === 1 ? $this->regions_db : $this->cities_db, $seek, $max);
        } else {
            fseek($this->fh, $this->info[$type === 1 ? 'regions_begin' : 'cities_begin'] + $seek);
            $raw = fread($this->fh, $max);
        }

        return $this->unpack($this->pack[$type] ?? '', $raw);
    }

    /**
     * Разбор записи города (seek — число от get_num)
     * Если $full == true — возвращает city+region+country, иначе — city+country(min)
     */
    protected function parseCity(int|false $seek, bool $full = false): array|false
    {
        if (!$this->pack) return false;
        if (!$seek && $seek !== 0) return false;

        $only_country = false;

        if ($seek < $this->country_size) {
            // это страна
            $country = $this->readData($seek, $this->max_country, 0);
            $city = $this->unpack($this->pack[2] ?? '');
            $city['lat'] = $country['lat'] ?? 0;
            $city['lon'] = $country['lon'] ?? 0;
            $only_country = true;
            $country = $country ?? [];
        } else {
            $city = $this->readData($seek, $this->max_city, 2);
            $country = ['id' => $city['country_id'] ?? 0, 'iso' => $this->id2iso[$city['country_id'] ?? 0] ?? ''];
            unset($city['country_id']);
        }

        if ($full) {
            $region = $this->readData((int)($city['region_seek'] ?? 0), $this->max_region, 1);
            if (!$only_country) {
                $country = $this->readData((int)($region['country_seek'] ?? 0), $this->max_country, 0);
            }
            unset($city['region_seek']);
            unset($region['country_seek']);
            return ['city' => $city, 'region' => $region, 'country' => $country];
        }

        unset($city['region_seek']);
        return ['city' => $city, 'country' => ['id' => $country['id'] ?? 0, 'iso' => $country['iso'] ?? '']];
    }

    /**
     * Универсальный unpack для формата pack (строка типа 'n:name/...') — переписан с минимальными изменениями от оригинала.
     * $pack — строка формата полей, $item — бинарная строка данных
     */
    protected function unpack(string $pack, string $item = ''): array
    {
        $unpacked = [];
        $empty = $item === '';
        if ($pack === '') return $unpacked;

        $parts = explode('/', $pack);
        $pos = 0;

        foreach ($parts as $p) {
            if ($p === '') continue;
            [$type, $name] = array_pad(explode(':', $p, 2), 2, '');
            $type0 = $type[0] ?? '';
            switch ($type0) {
                case 't':
                case 'T':
                    $l = 1;
                    break;
                case 's':
                case 'n':
                case 'S':
                    $l = 2;
                    break;
                case 'm':
                case 'M':
                    $l = 3;
                    break;
                case 'd':
                    $l = 8;
                    break;
                case 'c':
                    $l = (int)substr($type, 1);
                    break;
                case 'b':
                    if ($empty) {
                        $l = 0;
                    } else {
                        $nulpos = strpos($item, "\0", $pos);
                        $l = ($nulpos === false) ? strlen($item) - $pos : $nulpos - $pos;
                    }
                    break;
                default:
                    $l = 4;
            }

            $val = $empty ? '' : substr($item, $pos, max(0, $l));
            $v = null;

            switch ($type0) {
                case 't':
                    $v = $val === '' ? 0 : current(unpack('c', $val));
                    break;
                case 'T':
                    $v = $val === '' ? 0 : current(unpack('C', $val));
                    break;
                case 's':
                    $v = $val === '' ? 0 : current(unpack('s', $val));
                    break;
                case 'S':
                    $v = $val === '' ? 0 : current(unpack('S', $val));
                    break;
                case 'm':
                    // 3 bytes signed
                    if ($val === '' || strlen($val) < 3) {
                        $v = 0;
                    } else {
                        // знак из старшего бита третьего байта
                        $pad = (ord($val[2]) & 0x80) ? "\xff" : "\0";
                        $v = current(unpack('l', $val . $pad));
                    }
                    break;
                case 'M':
                    // 3 bytes unsigned
                    if ($val === '' || strlen($val) < 3) {
                        $v = 0;
                    } else {
                        $v = current(unpack('L', $val . "\0"));
                    }
                    break;
                case 'i':
                    $v = $val === '' ? 0 : current(unpack('l', $val));
                    break;
                case 'I':
                    $v = $val === '' ? 0 : current(unpack('L', $val));
                    break;
                case 'f':
                    $v = $val === '' ? 0.0 : current(unpack('f', $val));
                    break;
                case 'd':
                    $v = $val === '' ? 0.0 : current(unpack('d', $val));
                    break;
                case 'n':
                    $v = ($val === '' ? 0 : current(unpack('s', $val))) / pow(10, (int)($type[1] ?? 0));
                    break;
                case 'N':
                    $v = ($val === '' ? 0 : current(unpack('l', $val))) / pow(10, (int)($type[1] ?? 0));
                    break;
                case 'c':
                    $v = rtrim($val, ' ');
                    break;
                case 'b':
                    $v = $val;
                    // for 'b' move position by l+1 to skip trailing null in original logic
                    $l++; // next $pos will include the null
                    break;
                default:
                    $v = $val;
            }

            $pos += $l;
            $unpacked[$name] = is_array($v) ? current($v) : $v;
        }

        return $unpacked;
    }

    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }
}
