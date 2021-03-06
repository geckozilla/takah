<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Suratmasuk extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	function __construct()
	{
	    parent::__construct();
		$this->db3   = $this->load->database('sapkdb', TRUE);
	    $this->db1   = $this->load->database('default', TRUE);
		//$this->load->library('openkm');
	}
	
	public function index()
	{
		//$this->load->library('form_validation');
		
		$data['penerima']		= $this->_get_app_user();
		$data['instansi']       = $this->_get_instansi();
		$data['message']        = '';
		$this->load->view('pencatatan/vsurat_masuk',$data);
	}
	
	public function save()
	{
	   	$instansi				= $this->input->post('instansi');
		$nosurat				= $this->input->post('nosurat');
		$tglsurat				= $this->input->post('tglsurat');
		$tglterima				= $this->input->post('tglterima');
		$jumlahsurat			= $this->input->post('jumlahsurat');
		$nip                    = $this->input->post('nip');
		$perihal				= $this->input->post('perihal');
		$keterangan				= $this->input->post('keterangan');
		$penerima				= $this->input->post('penerima');
		$tnip                   = $this->input->post('tnip');
		$status_nip				= $this->input->post('status_nip');
		
		if($status_nip == '1' ) $nip = $tnip;
		
		
		$surat_masuk = array('tgl_terima'			=> date('Y-m-d',strtotime($tglterima)),
							 'nomor_surat'			=> $nosurat,
							 'tgl_surat'			=> date('Y-m-d',strtotime($tglsurat)),
							 'perihal'				=> $perihal,
							 'keterangan'			=> $keterangan,
							 'nip'					=> $nip,
							 'kode_instansi'		=> $instansi,
							 'id_pengirim'			=> $this->session->userdata('user_id'),
							 'id_penerima'			=> $penerima,
							 'jumlah_surat'			=> $jumlahsurat,
		
		);
		
			// session set base on user activity
	    $arr_session		= $surat_masuk;
		
	    $this->session->set_userdata($arr_session);
			   
		
		$this->db1->insert('surat_masuk',$surat_masuk);
		
		$data['instansi']       = $this->_get_instansi();
		$data['penerima']		= $this->_get_app_user();
		$data['message']        = 'Save successfuly...';
		$this->load->view('pencatatan/vsurat_masuk',$data);   
	}
	
	function laporan()
	{
	    $data['instansi']              = $this->_get_instansi();
		$data['pelaksana']			   = $this->_get_app_user();
	    $this->load->view('laporan/vsuratmasuk',$data);
	}
	
	function cetakLaporan()
	{
	    $instansi    	= $this->input->post('instansi');
		$penerima    	= $this->input->post('penerima');
		$reportrange 	= $this->input->post('reportrange');
		$status         = $this->input->post('status');
		$xreportrange	= explode("-",$reportrange);
	    $startdate		= $xreportrange[0];
	    $enddate		= $xreportrange[1];

		
		if($instansi != '')
		{
		   $sql_instansi  = " AND a.kode_instansi='$instansi' ";
		}
		else
		{
		   $sql_instansi  = " ";
		
		}
		
		if($penerima != '')
		{
		    $sql_penerima   = " AND a.id_penerima='$penerima' ";
		}
		else
		{
		    $sql_penerima  = " ";
		}
		
		if($status == '1')
		{
		   $sql_status  = " AND a.status_penerima IS NOT NULL ";
		}
		elseif($status == '2')
		{
		   $sql_status  = " AND a.status_penerima IS NULL";
		}
		else
		{
		   $sql_status  = " ";
		}
		
		
		$sql="SELECT a.*, b.action_disposisi ,c.PNS_PNSNAM FROM  surat_masuk a 
		LEFT JOIN action_disposisi  b ON b.id_surat=a.id  
		LEFT JOIN mirror.pupns c ON a.nip = c.PNS_NIPBARU
		WHERE 1=1 AND ( DATE( a.created_date ) BETWEEN STR_TO_DATE( '$startdate', '%d/%m/%Y ' )
AND STR_TO_DATE( '$enddate', '%d/%m/%Y') OR DATE( a.update_date ) BETWEEN STR_TO_DATE( '$startdate', '%d/%m/%Y ' )
AND STR_TO_DATE( '$enddate', '%d/%m/%Y') ) $sql_penerima $sql_status $sql_instansi
		ORDER BY a.tgl_terima,a.id ASC";
		
		//var_dump($sql); exit;
		$q    = $this->db1->query($sql);
		
        // creating xls file
		$now              = date('dmYHis');
		$filename         = "SURATMASUK".$now.".xls";
		
		header('Pragma:public');
		header('Cache-Control:no-store, no-cache, must-revalidate');
		header('Content-type:application/x-msdownload');
		header('Content-Disposition:attachment; filename='.$filename);                      
		header('Expires:0'); 
		
		$html   = 'LAPORAN SURAT MASUK PERIODE '.$startdate.'  sampai dengan '. $enddate.'<br/>
				   Aplikasi Tata Naskah  Kepegawaian<br/><br/>';
		$html .= '<style> .str{mso-number-format:\@;}</style>';
		$html .= '<table border="1">';					
		$html .='<tr>
					<th>NO</th>
					<th>NO AGENDA</th>
					<th>TGL TERIMA</th>
					<th>TGL SURAT</th>
					<th>NOMOR SURAT</th>
					<th>PERIHAL</th>
					<th>DISPOSISI</th>
					<th>NIP</th>
					<th>NAMA</th>
					<th>INSTANSI</th>
					<th>PENGIRIM</th>
					<th>KEPADA</th>
					<th>STATUS</th>
					<th>UPDATE DATE</th>	
					<th>JUMLAH SURAT</th>
					<th>KETERANGAN</th>'; 
		$html 	.= '</tr>';
		if($q->num_rows() > 0){
			$i = 1;		        
			foreach ($q->result() as $r) {
			   	$html .= "<tr><td>$i</td>";
				$html .= "<td>{$r->id}</td>";
				$html .= "<td>{$r->tgl_terima}</td>";
				$html .= "<td>{$r->tgl_surat}</td>";
				$html .= "<td>{$r->nomor_surat}</td>";
				$html .= "<td>{$r->perihal}</td>";
				$html .= "<td>{$r->keterangan}</td>";
				$html .= "<td class=str width=150>{$r->nip}</td>";
				$html .= "<td class=str width=150>{$r->PNS_PNSNAM}</td>";
				$html .= "<td>{$this->_get_nama_instansi($r->kode_instansi)}</td>";
				$html .= "<td>{$this->_get_nama_orang($r->id_pengirim)}</td>";
				$html .= "<td>{$this->_get_nama_orang($r->id_penerima)}</td>";
				$html .= "<td align=center>".($r->status_penerima ? '&#10004;' : ' ')."</td>";
				$html .= "<td>{$r->update_date}</td>";
				$html .= "<td>{$r->jumlah_surat}</td>";
                $html .= "<td>{$r->action_disposisi}</td>";				
				$html .= "</tr>";
				$i++;
			}
			$html .="</table>";
			echo $html;
		}else{
			$html .="<tr><td  colspan=6 >There is no data found</td></tr></table>";
			echo $html;
		} 	  
	}
	
	function _get_instansi()
	{
	    return $this->db3->query("SELECT  * FROM instansi order by INS_KODINS ASC");
	}
	
	
	
	function get_pns()
	{
	   $search   = $this->input->get('q');
	   
	   $sql="SELECT PNS_NIPBARU as id,CONCAT( PNS_NIPBARU ,' - ', PNS_PNSNAM)  as text FROM PUPNS WHERE PNS_NIPBARU LIKE '$search%' ORDER BY PNS_PNSNAM ASC";
	   $query= $this->db3->query($sql);
	   $ret['results'] = $query->result_array();
	   echo json_encode($ret);
	}
	
	function _get_app_user()
	{
	    if($this->session->userdata('level') == 'kasie')
		{
		   $sql_limit_user = " ";
		}
		else
		{
		   $id  = $this->session->userdata('user_id');
		   $sql_limit_user  = " AND id='$id'";
		
		}
	    
		$sql="SELECT id,nama FROM app_user where 1=1 $sql_limit_user AND level != 'kasie' ORDER BY nama ASC";
		
		return $this->db1->query($sql);
		
	}
	
	function _get_nama_instansi($id)
	{
	   $sql="SELECT INS_NAMINS FROM instansi WHERE INS_KODINS='$id' ";
       $query  = $this->db3->query($sql);
	   $row    = $query->row();
	   
	   return $row->INS_NAMINS;  
	
	}
	
	function _get_nama_orang($id)
	{
	
	   $sql="SELECT nama FROM app_user WHERE id='$id' ";
	   $query  = $this->db1->query($sql);
	   $row    = $query->row();
	   
	   return $row->nama;  
	
	}
	
	function search()
	{
	    $search =$this->input->post('search');
		
		if($search)
		{
		   $sql_search = "AND ( a.nip LIKE '%$search%'  OR a.nomor_surat LIKE '%$search%') ";
		}
		else
		{
		   $sql_search ="";
		}
		
		$user_id = $this->session->userdata('user_id');		
		
		$sql="SELECT a.*, DATE_FORMAT(a.created_date, '%d-%m-%Y') tgl_input,
		DATE_FORMAT(a.tgl_terima, '%d-%m-%Y') tgl_ter, 
		DATE_FORMAT(a.tgl_surat, '%d-%m-%Y') tgl_sur,
		b.INS_NAMINS FROM surat_masuk a
		INNER JOIN mirror.instansi b ON a.kode_instansi = b.INS_KODINS 
		WHERE 1=1 $sql_search  
		AND (id_penerima='$user_id' OR id_pengirim='$user_id')
		LIMIT 10";
		$query = $this->db1->query($sql);
		
		$data['record']    = $query; 
		$data['message']   ='';
		$data['instansi']  = $this->_get_instansi();
	    $this->load->view('search/vsuratmasuk',$data);
	}
	
	function get_surat()
	{
	   $id = $this->input->post('surat_id');
	   
	   $sql="SELECT *,DATE_FORMAT(created_date,'%d-%m-%Y') tgl_input,
	   DATE_FORMAT(tgl_terima,'%d-%m-%Y') tgl_ter,
	   DATE_FORMAT(tgl_surat,'%d-%m-%Y') tgl_sur
	   FROM takah.surat_masuk where id='$id' ";
	   $query = $this->db1->query($sql);
		
	   echo json_encode($query->result_array());
	   
	
	}
	
	function update()
	{
	    $id  			        = $this->input->post('surat_id');
		$tgl_input  			= $this->input->post('tgl_input');
		$nip  					= $this->input->post('nip');
		$instansi  		    	= $this->input->post('instansi');
		$no_surat	  			= $this->input->post('no_surat');
		$tgl_surat	  			= $this->input->post('tgl_surat');
		$tgl_terima		  		= $this->input->post('tgl_terima');
		$perihal		  		= $this->input->post('perihal');
		
		$data = array('created_date'     => date('Y-m-d', strtotime($tgl_input)),
					  'nip'              => $nip,
					  'kode_instansi'    => $instansi,
					  'nomor_surat'      => strtoupper($no_surat),
					  'tgl_surat'        => date('Y-m-d', strtotime($tgl_surat)),
					  'tgl_terima'       => date('Y-m-d', strtotime($tgl_terima)),
					  'perihal'   		 => $perihal,
		
		);
		
		$this->db1->where('id',$id);
	    $this->db1->update('surat_masuk', $data);
		
	}
	
	function delete()
	{
	    $id  			    = $this->input->post('suratdel_id');
		$this->db1->where('id',$id);
	    $this->db1->delete('surat_masuk');
	
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */