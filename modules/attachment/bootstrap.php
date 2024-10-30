<?

Click::$meta['config']['attachment'] = array(
  'fpath'=>CLICKLIB_ATTACHMENT_FPATH ."/data",
  'vpath'=>CLICKLIB_ATTACHMENT_VPATH ."/data",
);

define('ATTACHMENT_UPLOAD_FPATH', Click::$meta['config']['attachment']['fpath']);
define('ATTACHMENT_UPLOAD_VPATH', Click::$meta['config']['attachment']['vpath']);

ensure_writable_folder(ATTACHMENT_UPLOAD_FPATH);