<?php defined('BASEPATH') OR exit('No direct script access allowed');

class kemasan_produk extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('m_kemasan_produk');
		if (isset($_SESSION['user_name'])) {
			$this->load->view('theme_default/setting');
		} else {
			session_destroy();
			redirect(base_url() . 'login', 'location');
		}
	}
	
	public function index() {
		$data = array(
			'title' => 'Kemasan Produk',
			'breadcrumb_home_active' => '',
            'breadcrumb' => '<li>Logistik</li>
			                 <li>Manajemen Data</li>
			                 <li>Produk</li>
                             <li><a href="logistik/kemasan_produk">Kemasan Produk</a></li>',
			'page_icon' => 'icon-tag',
			'page_title' => 'Kemasan Produk',
			'page_subtitle' => 'Data Master Kemasan Produk',
			'get_jenis_kemasan' => $this->m_kemasan_produk->get_jenis_kemasan(),
			'custom_scripts' => "<script type='text/javascript'>$('#menu_logistik_kemasan_produk').addClass('active');</script>"
		);
		$this->template->build('v_kemasan_produk', $data);
	}
	
	public function get_data() {
		/* 
		 * Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('id', 'nama_kemasan', 'deskripsi', 'jenis_kemasan', 'nama_jenis_kemasan', 'activated');
        
        // DB table to use
		$sTable = "(SELECT
						a.id,
						a.nama AS nama_kemasan,
						a.deskripsi,
						a.jenis_kemasan,
						b.nama AS nama_jenis_kemasan,
						a.activated
					FROM
						ck_produk_kemasan a
						LEFT OUTER JOIN ck_produk_kemasan_jenis b ON a.jenis_kemasan = b.id) 
						ck_produk_kemasan";
						
        $iDisplayStart = $this->input->get_post('iDisplayStart', true);
        $iDisplayLength = $this->input->get_post('iDisplayLength', true);
        $iSortCol_0 = $this->input->get_post('iSortCol_0', true);
        $iSortingCols = $this->input->get_post('iSortingCols', true);
        $sSearch = $this->input->get_post('sSearch', true);
        $sEcho = $this->input->get_post('sEcho', true);
    
        // Paging
        if (isset($iDisplayStart) && $iDisplayLength != '-1') {
            $this->db->limit($this->db->escape_str($iDisplayLength), $this->db->escape_str($iDisplayStart));
        }
        
        // Ordering
        if (isset($iSortCol_0)) {
            for ($i=0; $i<intval($iSortingCols); $i++) {
                $iSortCol = $this->input->get_post('iSortCol_'.$i, true);
                $bSortable = $this->input->get_post('bSortable_'.intval($iSortCol), true);
                $sSortDir = $this->input->get_post('sSortDir_'.$i, true);
    
                if ($bSortable == 'true') {
                    $this->db->order_by($aColumns[intval($this->db->escape_str($iSortCol))], $this->db->escape_str($sSortDir));
                }
            }
        }
        
        /* 
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        if (isset($sSearch) && !empty($sSearch)) {
            for ($i=0; $i<count($aColumns); $i++) {
                $bSearchable = $this->input->get_post('bSearchable_'.$i, true);
                
                // Individual column filtering
                if (isset($bSearchable) && $bSearchable == 'true') {
                    $this->db->or_like($aColumns[$i], $this->db->escape_like_str($sSearch));
                }
            }
        }
        
        // Select Data
        $this->db->select('SQL_CALC_FOUND_ROWS '.str_replace(' , ', ' ', implode(', ', $aColumns)), false);
        $rResult = $this->db->get($sTable);
    
        // Data set length after filtering
        $this->db->select('FOUND_ROWS() AS found_rows');
        $iFilteredTotal = $this->db->get()->row()->found_rows;
    
        // Total data set length
        $iTotal = $this->db->count_all($sTable);
    
        // Output
        $output = array(
            'sEcho' => intval($sEcho),
            'iTotalRecords' => $iTotal,
            'iTotalDisplayRecords' => $iFilteredTotal,
            'aaData' => array()
        );
        
		$no = $iDisplayStart;
				
        foreach ($rResult->result_array() as $aRow) {
            $row = array();
			
			$no++;
			$row[] = $no;
			$row[] = $aRow['nama_kemasan'];
			$row[] = $aRow['deskripsi'];
			$row[] = $aRow['nama_jenis_kemasan'];			
			if ($aRow['activated'] == '1') { // Aktif
				$row[] = '<span class="label label-success">Aktif</span>';
			} elseif ($aRow['activated'] == '0') { // Non Aktif
				$row[] = '<span class="label label-danger">Non Aktif</span>';
			}
			$row[] = '<button type="button" class="btn btn-default btn-xs btn-edit" value="'.$aRow['id'].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Edit&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</button>';
			
			$output['aaData'][] = $row;
        }
        echo json_encode($output);
    }
	
	function get_jenis_kemasan() {
		$data = $this->m_kemasan_produk->get_jenis_kemasan();
        if ($data->num_rows() > 0) {			
			foreach($data->result() as $r) {				
				echo "<option value='$r->id'>".$r->nama."</option>";
			}
        }
	}
	
  	function kemasan_produk_view() {
		$id = $this->input->post('id');
		$kemasan_produk = $this->m_kemasan_produk->kemasan_produk_view($id);
		foreach ($kemasan_produk->result_array() as $row) {
			$data['id'] = $row['id'];
			$data['nama_kemasan'] = $row['nama_kemasan'];
			$data['deskripsi'] = $row['deskripsi'];
			$data['jenis_kemasan'] = $row['jenis_kemasan'];
			$data['nama_jenis_kemasan'] = $row['nama_jenis_kemasan'];
			
		}
    	echo json_encode($data);
  	}

	function kemasan_produk_create() {
		$notifikasi = $this->m_kemasan_produk->kemasan_produk_create();
		if ($notifikasi == 1) {
			$notifikasi=" Selamat ! Anda berhasil";
		} else {
			$notifikasi=" Maaf ! Anda gagal";
		}
		$notifikasi = array('notifikasi' => $notifikasi);
		echo json_encode($notifikasi, true);
	}

	function kemasan_produk_update() {
		$notifikasi = $this->m_kemasan_produk->kemasan_produk_update();
		if ($notifikasi == 1) {
			$notifikasi=" Selamat ! Anda berhasil";
		} else {
			$notifikasi=" Maaf ! Anda gagal";
		}
		$notifikasi = array('notifikasi' => $notifikasi);
		echo json_encode($notifikasi, true);
	}

	function kemasan_produk_delete($id = '') {
		$notifikasi = $this->m_kemasan_produk->kemasan_produk_delete($id);
		if ($notifikasi == 1) {
			$notifikasi=" Selamat ! Anda berhasil";
		} else {
			$notifikasi=" Maaf ! Anda gagal";
		}
		$notifikasi = array('notifikasi' => $notifikasi);
		echo json_encode($notifikasi, true);
	}

}