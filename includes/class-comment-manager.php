<?php 

/**
* Comment Manager
*/
class Comment_Manager
{
	private $comment_id;

	function __construct($comment_id)
	{
		$this->comment_id = $comment_id;
	}

	public function approve(){
		wp_set_comment_status($this->comment_id, 'approve');
	}

	public function reply($message, $user_id){
		
		$comment = get_comment( $this->comment_id );

		$comment_data = array(
				'comment_post_ID' 	=> $comment->comment_post_ID,
				'comment_content' 	=> $message,
				'comment_parent' 		=> $this->comment_id,
				'comment_approved' 	=> 1,
				'user_id'			=> $user_id
			);	

		wp_insert_comment( $comment_data );
	}

	public function spam(){
		wp_spam_comment($this->comment_id);
	}

	public function trash(){
		wp_trash_comment($this->comment_id);
	}

}