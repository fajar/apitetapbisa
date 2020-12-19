<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2016, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2016, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$lang['db_invalid_connection_str'] = 'Tidak dapat menentukan pengaturan database berdasarkan string connection yang Anda kirimkan.';
$lang['db_unable_to_connect'] = 'Tidak dapat terhubung ke server database Anda menggunakan pengaturan yang disediakan.';
$lang['db_unable_to_select'] = 'Tidak dapat memilih database yang dipilih: %s';
$lang['db_unable_to_create'] = 'Tidak dapat membuat database yang dipilih: %s';
$lang['db_invalid_query'] = 'Permintaan yang Anda kirim tidak valid.';
$lang['db_must_set_table'] = 'Anda harus mengatur tabel database agar dapat digunakan dengan query.';
$lang['db_must_use_set'] = 'Anda harus menggunakan metode "set" untuk memperbarui entri.';
$lang['db_must_use_index'] = 'You must specify an index to match on for batch updates.';
$lang['db_batch_missing_index'] = 'Satu baris atau lebih yang diajukan untuk pembaruan batch tidak memiliki specified index.';
$lang['db_must_use_where'] = 'Pembaruan tidak diizinkan kecuali jika mengandung "where".';
$lang['db_del_must_use_where'] = 'Penghapusan tidak diizinkan kecuali jika mengandung "where" atau "like".';
$lang['db_field_param_missing'] = 'Untuk fetch fields membutuhkan nama tabel sebagai parameter.';
$lang['db_unsupported_function'] = 'Fitur ini tidak tersedia untuk database yang Anda gunakan.';
$lang['db_transaction_failure'] = 'Kegagalan transaksi: Rollback performed.';
$lang['db_unable_to_drop'] = 'Tidak dapat drop database yang dipilih.';
$lang['db_unsupported_feature'] = 'Fitur ini yang tidak didukung dari platform database yang Anda gunakan.';
$lang['db_unsupported_compression'] = 'Format kompresi file yang Anda pilih tidak didukung oleh server Anda.';
$lang['db_filepath_error'] = 'Tidak dapat menulis data ke file path yang telah Anda kirim.';
$lang['db_invalid_cache_path'] = 'Cache path yang Anda kirim tidak valid atau writable.';
$lang['db_table_name_required'] = 'Diperlukan nama tabel untuk operasi ini.';
$lang['db_column_name_required'] = 'Diperlukan nama kolom untuk operasi ini.';
$lang['db_column_definition_required'] = 'Definisi kolom diperlukan.';
$lang['db_unable_to_set_charset'] = 'Tidak dapat mengatur karakter koneksi klien set: %s';
$lang['db_error_heading'] = 'Terjadi Kesalahan Database';
