<?php
namespace Services;

use Models\QRCode;

class QRService {
    private $qrModel;

    public function __construct() {
        $this->qrModel = new QRCode();
    }

    public function generate($type, $location, $adminUserId) {
        $code = 'SGN_' . strtoupper($type === 'sign_in' ? 'IN' : 'OUT') . '_' . bin2hex(random_bytes(5));
        $data = [
            'code' => $code,
            'type' => $type,
            'location' => $location,
            'created_by' => $adminUserId,
            'is_active' => 1
        ];
        $id = $this->qrModel->create($data);
        return $this->qrModel->find($id);
    }

    public function revoke($code) {
        $qr = $this->qrModel->findByCode($code);
        if ($qr) {
            return $this->qrModel->update($qr['id'], ['is_active' => 0]);
        }
        return false;
    }

    public function getActive($type) {
        return $this->qrModel->getActive($type);
    }
}