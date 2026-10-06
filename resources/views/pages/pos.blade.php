@extends('layouts.app')

@section('content')
<style>
	.pos-layout {
		display: flex;
		gap: 24px;
		align-items: flex-start;
		flex-wrap: wrap;
	}

	.pos-search-panel {
		flex: 1.3;
		min-width: 320px;
	}

	.pos-cart-panel {
		flex: 1;
		min-width: 340px;
		position: sticky;
		top: 24px;
	}

	/* Same pill treatment as the Transaction Logs tabs, so "filter" looks the
	   same wherever it appears. */
	.pos-categories {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-top: 16px;
	}

	.pos-cat-chip {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 8px 14px;
		border: 1.5px solid var(--color-border);
		border-radius: var(--radius-pill);
		background: var(--color-surface);
		color: var(--color-text-muted);
		font: inherit;
		font-size: 0.88rem;
		font-weight: 600;
		cursor: pointer;
		transition: border-color var(--dur-fast) var(--ease-out),
			background var(--dur-fast) var(--ease-out),
			color var(--dur-fast) var(--ease-out);
	}

	.pos-cat-chip:hover {
		border-color: var(--color-primary);
		color: var(--color-primary);
	}

	.pos-cat-chip:focus-visible {
		outline: 2px solid var(--color-primary);
		outline-offset: 2px;
	}

	.pos-cat-chip.is-active {
		background: var(--color-primary);
		border-color: var(--color-primary);
		color: #fff;
	}

	.pos-cat-chip__count {
		font-size: 0.78rem;
		font-weight: 700;
		padding: 1px 7px;
		border-radius: var(--radius-pill);
		background: var(--color-neutral-bg);
		color: var(--color-text-muted);
	}

	.pos-cat-chip.is-active .pos-cat-chip__count {
		background: rgba(255, 255, 255, 0.24);
		color: #fff;
	}

	.pos-results-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		gap: 14px;
		margin-top: 18px;
		max-height: 560px;
		overflow-y: auto;
		padding-right: 4px;
	}

	.pos-product-card {
		border: 1.5px solid #e5e7eb;
		border-radius: var(--radius-md);
		padding: 14px 16px;
		cursor: pointer;
		background: var(--color-page-bg);
		transition: border-color 0.15s, transform 0.1s;
	}

	.pos-product-card:hover {
		border-color: var(--color-primary);
		transform: translateY(-1px);
	}

	.pos-product-card.out-of-stock,
	.pos-product-card.expired {
		opacity: 0.55;
		cursor: not-allowed;
	}

	.pos-product-expired-label {
		color: var(--color-danger);
		font-weight: 700;
	}

	.pos-product-name {
		font-weight: 700;
		color: var(--color-text);
		margin-bottom: 4px;
	}

	.pos-product-meta {
		font-size: 0.85rem;
		color: var(--color-text-muted);
		margin-bottom: 6px;
	}

	.pos-product-price {
		font-weight: 700;
		color: var(--color-primary);
	}

	.pos-empty-state {
		text-align: center;
		color: var(--color-text-muted);
		padding: 40px 10px;
	}

	.pos-qty-btn {
		width: 26px;
		height: 26px;
		border-radius: 50%;
		border: 1.5px solid #e5e7eb;
		background: #fff;
		cursor: pointer;
		font-weight: 700;
		line-height: 1;
	}

	.pos-qty-btn:hover {
		border-color: var(--color-primary);
		color: var(--color-primary);
	}

	.pos-cart-total-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 1.3rem;
		font-weight: 800;
		padding: 16px 4px;
		border-top: 2px solid #e5e7eb;
		margin-top: 8px;
	}

	.pos-cart-empty {
		text-align: center;
		color: var(--color-text-muted);
		padding: 30px 10px;
	}

	.pos-tender {
		padding: 4px 4px 14px;
	}

	.pos-tender__label {
		display: block;
		font-weight: 700;
		font-size: 0.95rem;
		color: var(--color-text);
		margin-bottom: 8px;
	}

	.pos-tender input {
		font-size: 1.15rem;
		font-weight: 600;
	}

	.pos-quick-cash {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-top: 10px;
	}

	.pos-quick-cash__btn {
		flex: 1 1 auto;
		min-width: 58px;
		padding: 8px 10px;
		border: 1.5px solid var(--color-border);
		border-radius: var(--radius-sm);
		background: var(--color-page-bg);
		color: var(--color-text);
		font: inherit;
		font-weight: 600;
		font-size: 0.9rem;
		cursor: pointer;
		transition: border-color var(--dur-fast) var(--ease-out),
			background var(--dur-fast) var(--ease-out),
			color var(--dur-fast) var(--ease-out);
	}

	.pos-quick-cash__btn:hover {
		border-color: var(--color-primary);
		background: var(--color-primary-soft);
		color: var(--color-primary);
	}

	.pos-quick-cash__btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.pos-change-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 14px;
		padding: 12px 14px;
		border-radius: var(--radius-sm);
		font-size: 1.15rem;
		font-weight: 800;
		background: var(--color-success-bg);
		color: var(--color-success-text);
		transition: background var(--dur-fast) var(--ease-out),
			color var(--dur-fast) var(--ease-out);
	}

	/* Neutral until there is enough cash to complete the sale. */
	.pos-change-row.is-idle {
		background: var(--color-neutral-bg);
		color: var(--color-text-muted);
	}
</style>

<div class="page-shell">
	<div class="dashboard-hero">
		<div>
			<div class="dashboard-hero-greeting">Point of Sale</div>
			<div class="dashboard-hero-sub">Scan or search a product, build the cart, then checkout.</div>
		</div>
	</div>

	<div class="pos-layout">
		<!-- Left: product lookup -->
		<div class="card-panel pos-search-panel">
			<h5 style="margin: 0 0 4px 0; font-weight: 700; color: var(--color-primary);">Product Lookup</h5>
			<div class="form-input-group">
				<svg class="form-input-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
					<circle cx="11" cy="11" r="8" />
					<line x1="21" y1="21" x2="16.65" y2="16.65" />
				</svg>
				<input type="text" id="posSearchInput" placeholder="Scan barcode or search by name / SKU..." autocomplete="off" autofocus>
			</div>

			@php
				// Built from the data, not a hardcoded list, so a category added
				// through Add Product shows up here automatically. The
				// "Uncategorised" bucket matters because products.category is
				// nullable — without it such a product would have no chip and
				// would be unreachable from the grid.
				$posCategories = $products
					->groupBy(fn ($p) => trim((string) $p->category) ?: 'Uncategorised')
					->map->count()
					->sortKeys();
			@endphp

			<div class="pos-categories" id="posCategories" role="group" aria-label="Filter products by category">
				<button type="button" class="pos-cat-chip is-active" data-category="" aria-pressed="true">
					All <span class="pos-cat-chip__count">{{ $products->count() }}</span>
				</button>
				@foreach($posCategories as $categoryName => $categoryCount)
					<button type="button" class="pos-cat-chip" data-category="{{ $categoryName }}" aria-pressed="false">
						{{ $categoryName }} <span class="pos-cat-chip__count">{{ $categoryCount }}</span>
					</button>
				@endforeach
			</div>

			<div id="posResults" class="pos-results-grid"></div>
		</div>

		<!-- Right: cart -->
		<div class="card-panel pos-cart-panel">
			<h5 style="margin: 0 0 14px 0; font-weight: 700; color: var(--color-primary);">Cart</h5>
			<div class="data-table-container" style="margin-bottom: 0;">
				<table class="data-table" id="posCartTable">
					<thead>
						<tr>
							<th>Item</th>
							<th>Price</th>
							<th>Qty</th>
							<th>Subtotal</th>
							<th></th>
						</tr>
					</thead>
					<tbody id="posCartBody">
						<tr id="posCartEmptyRow">
							<td colspan="5" class="pos-cart-empty">Cart is empty. Add a product from the left panel.</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="pos-cart-total-row">
				<span>Total</span>
				<span id="posCartTotal">&#8369;0.00</span>
			</div>

			<div class="pos-tender">
				<label for="posAmountTendered" class="pos-tender__label">Amount Received</label>
				<div class="form-input-group">
					<span class="form-input-icon currency-icon">&#8369;</span>
					<input type="number" id="posAmountTendered" min="0" step="0.01" inputmode="decimal"
						placeholder="0.00" autocomplete="off">
				</div>

				<div class="pos-quick-cash" id="posQuickCash">
					<button type="button" class="pos-quick-cash__btn" data-exact>Exact</button>
					<button type="button" class="pos-quick-cash__btn" data-cash="20">&#8369;20</button>
					<button type="button" class="pos-quick-cash__btn" data-cash="50">&#8369;50</button>
					<button type="button" class="pos-quick-cash__btn" data-cash="100">&#8369;100</button>
					<button type="button" class="pos-quick-cash__btn" data-cash="500">&#8369;500</button>
					<button type="button" class="pos-quick-cash__btn" data-cash="1000">&#8369;1000</button>
				</div>

				{{-- Starts neutral: renderCart() only runs once the cart changes,
					 so without is-idle an empty cart shows a green "Change" row. --}}
				<div class="pos-change-row is-idle" id="posChangeRow">
					<span>Change</span>
					<span id="posChangeValue">&#8369;0.00</span>
				</div>
			</div>

			<button type="button" id="posCheckoutBtn" class="btn-action edit" style="width: 100%; padding: 14px; font-size: 1.05rem;" disabled>
				Checkout
			</button>
		</div>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	$(function () {
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		var allProducts = @json($products);
		var cart = []; // { product_id, name, sku, price, qty, stock }
		var cartTotal = 0;
		var searchTimer = null;

		function peso(value) {
			return '₱' + Number(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
		}

		function renderResults(products) {
			var $results = $('#posResults');
			$results.empty();

			if (!products.length) {
				$results.append('<div class="pos-empty-state">' +
					(activeCategory ? 'No products in ' + escapeHtml(activeCategory) + '.' : 'No products found.') +
					'</div>');
				return;
			}

			products.forEach(function (p) {
				var outOfStock = Number(p.stock) <= 0;
				var expired = !!p.expiry_date && new Date(p.expiry_date) < new Date();
				var blocked = outOfStock || expired;
				var metaLine = p.sku ? 'SKU: ' + escapeHtml(p.sku) + ' &middot; ' : '';
				metaLine += expired ? '<span class="pos-product-expired-label">EXPIRED</span>' : 'Stock: ' + p.stock;

				var $card = $('<div>')
					.addClass('pos-product-card' + (outOfStock ? ' out-of-stock' : '') + (expired ? ' expired' : ''))
					.attr('data-id', p.id)
					.html(
						'<div class="pos-product-name">' + escapeHtml(p.name) + '</div>' +
						'<div class="pos-product-meta">' + metaLine + '</div>' +
						'<div class="pos-product-price">' + peso(p.price) + '</div>'
					);

				if (!blocked) {
					$card.on('click', function () {
						addToCart(p);
					});
				}

				$results.append($card);
			});
		}

		function escapeHtml(str) {
			return $('<div>').text(str == null ? '' : str).html();
		}

		// null = the "All" chip. The whole catalogue is already embedded in this
		// page, so switching category needs no request.
		var activeCategory = null;

		function categoryOf(product) {
			var c = (product.category || '').trim();
			return c === '' ? 'Uncategorised' : c;
		}

		function productsInActiveCategory() {
			if (activeCategory === null) return allProducts;
			return allProducts.filter(function (p) { return categoryOf(p) === activeCategory; });
		}

		function showInitialProducts() {
			renderResults(productsInActiveCategory());
		}

		function setActiveCategory(category) {
			activeCategory = category;

			$('#posCategories .pos-cat-chip').each(function () {
				var mine = $(this).data('category') === undefined ? '' : String($(this).data('category'));
				var on = (category === null && mine === '') || mine === category;
				$(this).toggleClass('is-active', on).attr('aria-pressed', on ? 'true' : 'false');
			});
		}

		$('#posCategories').on('click', '.pos-cat-chip', function () {
			var value = String($(this).data('category') || '');
			setActiveCategory(value === '' ? null : value);

			// Browsing a category is a fresh start; a stale query would other-
			// wise keep the grid showing search results.
			$('#posSearchInput').val('');
			showInitialProducts();
		});

		function performLookup(query, onSingleExactMatch) {
			$.ajax({
				url: '/pos/lookup',
				method: 'GET',
				data: { q: query },
				success: function (response) {
					var products = response.products || [];
					renderResults(products);

					if (onSingleExactMatch && products.length === 1 &&
						products[0].sku && products[0].sku.toLowerCase() === query.toLowerCase()) {
						onSingleExactMatch(products[0]);
					}
				},
				error: function () {
					showToast('Product lookup failed.', 'error');
				}
			});
		}

		$('#posSearchInput').on('keyup', function (e) {
			if (e.key === 'Enter') {
				return; // handled by keydown below
			}

			var query = $(this).val().trim();
			clearTimeout(searchTimer);

			if (query === '') {
				showInitialProducts();
				return;
			}

			// Searching covers the whole catalogue: a scanned or typed item must
			// never be hidden because a category chip was left selected.
			setActiveCategory(null);

			searchTimer = setTimeout(function () {
				performLookup(query, null);
			}, 250);
		});

		// Barcode scanners type fast then send Enter - trigger an immediate
		// lookup and auto-add + clear the input if it is a single exact SKU match.
		$('#posSearchInput').on('keydown', function (e) {
			if (e.key !== 'Enter') {
				return;
			}
			e.preventDefault();

			var $input = $(this);
			var query = $input.val().trim();
			if (query === '') {
				return;
			}

			clearTimeout(searchTimer);
			performLookup(query, function (product) {
				addToCart(product);
				$input.val('');
				showInitialProducts();
			});
		});

		function addToCart(product) {
			var stock = Number(product.stock);
			var expired = !!product.expiry_date && new Date(product.expiry_date) < new Date();

			if (expired) {
				showToast('"' + product.name + '" is expired and cannot be sold.', 'error');
				return;
			}

			var existing = cart.find(function (item) { return item.product_id === product.id; });

			if (existing) {
				if (existing.qty >= stock) {
					showToast('Only ' + stock + ' unit(s) of "' + product.name + '" available.', 'error');
					return;
				}
				existing.qty += 1;
			} else {
				if (stock <= 0) {
					showToast('"' + product.name + '" is out of stock.', 'error');
					return;
				}
				cart.push({
					product_id: product.id,
					name: product.name,
					sku: product.sku,
					price: Number(product.price),
					qty: 1,
					stock: stock
				});
			}

			renderCart();
		}

		function changeQty(productId, delta) {
			var item = cart.find(function (i) { return i.product_id === productId; });
			if (!item) return;

			var newQty = item.qty + delta;
			if (newQty <= 0) {
				cart = cart.filter(function (i) { return i.product_id !== productId; });
			} else if (newQty > item.stock) {
				showToast('Only ' + item.stock + ' unit(s) of "' + item.name + '" available.', 'error');
				return;
			} else {
				item.qty = newQty;
			}

			renderCart();
		}

		function removeFromCart(productId) {
			cart = cart.filter(function (i) { return i.product_id !== productId; });
			renderCart();
		}

		function renderCart() {
			var $body = $('#posCartBody');
			$body.empty();

			if (!cart.length) {
				$body.append('<tr id="posCartEmptyRow"><td colspan="5" class="pos-cart-empty">Cart is empty. Add a product from the left panel.</td></tr>');
				$('#posCartTotal').text(peso(0));
				cartTotal = 0;
				updateChange();
				return;
			}

			var total = 0;

			cart.forEach(function (item) {
				var subtotal = item.qty * item.price;
				total += subtotal;

				var $row = $('<tr>').attr('data-product-id', item.product_id).html(
					'<td>' + escapeHtml(item.name) + (item.sku ? '<div style="font-size:0.8rem; color: var(--color-text-muted);">' + escapeHtml(item.sku) + '</div>' : '') + '</td>' +
					'<td>' + peso(item.price) + '</td>' +
					'<td>' +
					'<div style="display:flex; align-items:center; gap:8px;">' +
					'<button type="button" class="pos-qty-btn pos-qty-minus">-</button>' +
					'<span style="min-width:20px; text-align:center; display:inline-block;">' + item.qty + '</span>' +
					'<button type="button" class="pos-qty-btn pos-qty-plus">+</button>' +
					'</div>' +
					'</td>' +
					'<td>' + peso(subtotal) + '</td>' +
					'<td><button type="button" class="btn-action delete pos-remove-btn" style="padding: 4px 12px; font-size: 0.85rem;">Remove</button></td>'
				);

				$body.append($row);
			});

			$('#posCartTotal').text(peso(total));
			cartTotal = round2(total);
			updateChange();
		}

		// Money is compared and displayed in centavos-rounded floats; rounding at
		// every step keeps 0.1 + 0.2 style drift from making an exact payment look
		// one centavo short.
		function round2(v) {
			return Math.round((Number(v) + Number.EPSILON) * 100) / 100;
		}

		function tenderedValue() {
			var raw = $('#posAmountTendered').val();
			if (raw === '' || raw === null) return null;
			var n = Number(raw);
			return isFinite(n) && n >= 0 ? round2(n) : null;
		}

		/**
		 * Single source of truth for the change display and the checkout gate:
		 * checkout is only possible with a non-empty cart AND enough cash.
		 */
		function updateChange() {
			var $row = $('#posChangeRow');
			var tendered = tenderedValue();
			var hasCart = cart.length > 0;

			$('#posQuickCash button').prop('disabled', !hasCart);

			var diff = hasCart && tendered !== null ? round2(tendered - cartTotal) : null;

			// Not enough cash yet is treated the same as nothing entered: the
			// change stays at zero and Checkout stays disabled.
			if (diff === null || diff < 0) {
				$row.addClass('is-idle');
				$('#posChangeValue').text(peso(0));
				$('#posCheckoutBtn').prop('disabled', true);
				return;
			}

			$row.removeClass('is-idle');
			$('#posChangeValue').text(peso(diff));
			$('#posCheckoutBtn').prop('disabled', false);
		}

		$('#posAmountTendered').on('input change', updateChange);

		$('#posQuickCash').on('click', 'button', function () {
			if (!cart.length) return;
			var $btn = $(this);

			if ($btn.is('[data-exact]')) {
				$('#posAmountTendered').val(cartTotal.toFixed(2));
			} else {
				// Accumulate, so tapping 500 then 100 tenders 600 - the way a
				// cashier actually counts notes onto the counter.
				var current = tenderedValue() || 0;
				$('#posAmountTendered').val(round2(current + Number($btn.data('cash'))).toFixed(2));
			}

			updateChange();
		});

		$('#posCartBody').on('click', '.pos-qty-plus', function () {
			var productId = Number($(this).closest('tr').data('product-id'));
			changeQty(productId, 1);
		});

		$('#posCartBody').on('click', '.pos-qty-minus', function () {
			var productId = Number($(this).closest('tr').data('product-id'));
			changeQty(productId, -1);
		});

		$('#posCartBody').on('click', '.pos-remove-btn', function () {
			var productId = Number($(this).closest('tr').data('product-id'));
			removeFromCart(productId);
		});

		$('#posCheckoutBtn').on('click', function () {
			if (!cart.length) return;

			var tendered = tenderedValue();
			if (tendered === null || round2(tendered - cartTotal) < 0) {
				showToast('Enter an amount at least equal to the total.', 'error');
				$('#posAmountTendered').trigger('focus');
				return;
			}

			var btn = this;
			setButtonLoading(btn, true, 'Processing...');

			$.ajax({
				url: '/pos/checkout',
				method: 'POST',
				data: {
					items: cart.map(function (item) {
						return { product_id: item.product_id, quantity: item.qty };
					}),
					amount_tendered: tenderedValue()
				},
				success: function (response) {
					setButtonLoading(btn, false);
					// The change is the number the cashier has to act on, so it
					// leads the confirmation rather than a generic "completed".
					showToast('Sale completed. Change: ' + peso(response.change_due || 0), 'success');

					// Reflect updated stock in the in-memory product list and
					// currently rendered result cards.
					var updatedStock = response.updated_stock || {};
					Object.keys(updatedStock).forEach(function (productId) {
						var newStock = updatedStock[productId];
						var product = allProducts.find(function (p) { return String(p.id) === String(productId); });
						if (product) {
							product.stock = newStock;
						}
					});

					cart = [];
					$('#posAmountTendered').val('');
					renderCart();
					$('#posSearchInput').val('');
					showInitialProducts();

					if (response.receipt_url) {
						window.open(response.receipt_url, '_blank');
					}
				},
				error: function (xhr) {
					setButtonLoading(btn, false);
					var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Checkout failed';
					showToast(message, 'error');
				}
			});
		});

		showInitialProducts();
	});
</script>
@endsection
