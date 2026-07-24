<script>
  import { invalidateAll } from "$app/navigation";
  import { request, ApiError } from "$lib/api.js";
  import LoanCard from "$lib/LoanCard.svelte";

  let { data } = $props();

  // Derived from the load function, so it re-syncs automatically
  // whenever SvelteKit re-runs load (navigation or invalidateAll).
  let member = $derived(data.member);

  // Genuinely local UI state: not owned by the server.
  let barcode = $state("");
  let error = $state(null);
  let busy = $state(false);

  let canBorrow = $derived(
    member.allowance_left > 0 && !member.has_overdue && !busy,
  );

  async function submit(action) {
    busy = true;
    error = null;
    try {
      await action();
      await invalidateAll(); // re-runs +page.js, refreshing `data`
    } catch (e) {
      error =
        e instanceof ApiError
          ? e
          : new ApiError(0, "network", "Could not reach the API.");
    } finally {
      busy = false;
    }
  }

  function borrow(event) {
    event.preventDefault();
    const copyId = barcode.trim();
    if (copyId === "") return;

    return submit(async () => {
      await request(`/members/${member.id}/loans`, {
        method: "POST",
        body: { copy_id: copyId },
      });
      barcode = "";
    });
  }

  function returnCopy(copyId) {
    return submit(() =>
      request(`/members/${member.id}/loans/${copyId}`, { method: "DELETE" }),
    );
  }
</script>

<section>
  <header>
    <h1>{member.name}</h1>
    <p class="allowance">
      {member.allowance_left} of {member.max_loans} loans available
    </p>
  </header>

  {#if member.has_overdue}
    <p class="banner warn">
      This member has overdue books and cannot borrow until they are returned.
    </p>
  {/if}

  {#if error}
    <p class="banner error">
      {error.message}
      <span class="code">({error.code})</span>
    </p>
  {/if}

  <form onsubmit={borrow}>
    <input
      bind:value={barcode}
      placeholder="Scan or type a copy barcode"
      disabled={!canBorrow}
    />
    <button type="submit" disabled={!canBorrow || barcode.trim() === ""}>
      {busy ? "Working…" : "Borrow"}
    </button>
  </form>

  {#if member.loans.length === 0}
    <p class="empty">No books on loan right now.</p>
  {:else}
    <ul class="loans">
      {#each member.loans as loan (loan.id)}
        <li>
          <LoanCard
            {loan}
            disabled={busy}
            onReturn={() => returnCopy(loan.copy_id)}
          />
        </li>
      {/each}
    </ul>
  {/if}
</section>

<style>
  header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid #e2e2e2;
    padding-bottom: 0.75rem;
  }

  h1 {
    font-size: 1.4rem;
    margin: 0;
  }

  .allowance {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
  }

  .banner {
    padding: 0.7rem 0.9rem;
    border-radius: 6px;
    font-size: 0.9rem;
  }

  .warn {
    background: #fff4e5;
    color: #7a4b00;
  }

  .error {
    background: #fde8e8;
    color: #8a1c1c;
  }

  .code {
    opacity: 0.6;
    font-family: monospace;
  }

  form {
    display: flex;
    gap: 0.5rem;
    margin: 1.25rem 0;
  }

  input {
    flex: 1;
    padding: 0.55rem 0.7rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    font: inherit;
  }

  button {
    padding: 0.55rem 1.1rem;
    border: 0;
    border-radius: 6px;
    background: #1b1b1b;
    color: white;
    font: inherit;
    cursor: pointer;
  }

  button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }

  .loans {
    list-style: none;
    padding: 0;
    display: grid;
    gap: 0.6rem;
  }

  .empty {
    color: #777;
    font-style: italic;
  }
</style>
