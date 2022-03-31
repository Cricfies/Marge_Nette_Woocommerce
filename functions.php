function MSW_add_input_prix_revient_fiche_produit()
{
	global $post;

	woocommerce_wp_text_input( array(
		'id' => 'prix_revient',
		'label' => __( 'Prix de revient', 'woocommerce' ),
		'value' => get_post_meta( $post->ID, '_prix_revient', true ),
		'desc_tip'    => 'true',
		'description' => __('Saisissez le prix de revient de votre produit', 'woocommerce'),
		)
	);
}
add_action( 'woocommerce_product_options_pricing', 'MSW_add_input_prix_revient_fiche_produit' );

function MSW_add_input_prix_revient_variation_fiche_produit( $loop, $variation_data, $variation )
{
	woocommerce_wp_text_input( array(
		'id' => 'prix_revient[' . $loop . ']',
		'wrapper_class' => 'form-row form-row-first',
		'label' => __( 'Prix de revient', 'woocommerce' ),
		'value' => get_post_meta( $variation->ID, '_prix_revient', true ),
		'description' => __('Saisissez le prix de revient de votre produit', 'woocommerce'),
		)
	);
}
add_action( 'woocommerce_variation_options_pricing', 'MSW_add_input_prix_revient_variation_fiche_produit', 10, 3 );

function MSW_save_input_prix_revient_fiche_produit($post_id)
{
	if ( isset( $_POST['prix_revient'] ) ) update_post_meta( $post_id, '_prix_revient', esc_attr( $_POST['prix_revient'] ) );
}
add_action( 'woocommerce_process_product_meta', 'MSW_save_input_prix_revient_fiche_produit' );

function MSW_save_input_prix_revient_variation_fiche_produit( $variation_id, $i )
{
	if ( isset( $_POST['prix_revient'][$i] ) ) update_post_meta( $variation_id, '_prix_revient', esc_attr( $_POST['prix_revient'][$i] ) );
}
add_action( 'woocommerce_save_product_variation', 'MSW_save_input_prix_revient_variation_fiche_produit', 10, 2 );

function MSW_calculer_marge_nette_commande( $order_id )
{
	$order = wc_get_order ( $order_id );

	//Récupère le total de la commande
	$marge_nette = $order->get_subtotal();

	//Retire les promotions appliquées
	$marge_nette -= $order->get_total_discount();

	foreach ( $order->get_items() as $item_id => $item )
	{
		if ( $item->get_variation_id() != 0 ) $prix_revient = floatval( get_post_meta( $item->get_variation_id(), '_prix_revient', true ) );
		else $prix_revient = floatval( get_post_meta( $item->get_product_id(), '_prix_revient', true ) );

		//Retire le prix de revient du produit multiplié par la quantité commandée
		$marge_nette -= $item->get_quantity() * $prix_revient;
	}

	return $marge_nette;
}

function MSW_add_entete_marge_net_colonne_commandes( $columns )
{
	$columns['marge_nette'] = 'Marge nette';
    return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'MSW_add_entete_marge_net_colonne_commandes', 20 );

function MSW_add_contenu_marge_net_colonne_commandes( $column )
{
    global $post;

    if ( $column == 'marge_nette' )
	{
		$order = wc_get_order ( $post->ID );

		//Récupère le total de la commande
		$marge_nette = $order->get_subtotal();

		//Retire les promotions appliquées
		$marge_nette -= $order->get_total_discount();

		$prix_manquant = false;

		foreach ( $order->get_items() as $item_id => $item )
		{
			if ( $item->get_variation_id() != 0 ) $prix_revient = floatval( get_post_meta( $item->get_variation_id(), '_prix_revient', true ) );
			else $prix_revient = floatval( get_post_meta( $item->get_product_id(), '_prix_revient', true ) );

			if ($prix_revient == 0) $prix_manquant = true;

			//Retire le prix de revient du produit multiplié par la quantité commandée
			$marge_nette -= $item->get_quantity() * $prix_revient;
		}


		if ( $prix_manquant == false ) $marge_nette = wc_price( $marge_nette, array( 'currency' => $order->get_currency() ) );
		else $marge_nette = '—';

		echo $marge_nette;
    }
}
add_action( 'manage_shop_order_posts_custom_column', 'MSW_add_contenu_marge_net_colonne_commandes' );
