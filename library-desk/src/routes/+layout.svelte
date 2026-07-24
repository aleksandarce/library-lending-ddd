<script>
	let { children } = $props();
</script>

<div class="shell">
	<header>
		<a href="/">Lending Desk</a>
	</header>

	<main>
		{@render children()}
	</main>
</div>

<style>
	:global(body) {
		margin: 0;
		font-family: system-ui, sans-serif;
		background: #fafafa;
		color: #1b1b1b;
	}

	.shell {
		max-width: 48rem;
		margin: 0 auto;
		padding: 1.5rem;
	}

	header {
		margin-bottom: 1.5rem;
		padding-bottom: 1rem;
		border-bottom: 1px solid #e2e2e2;
	}

	header a {
		font-weight: 600;
		text-decoration: none;
		color: inherit;
	}
</style>
