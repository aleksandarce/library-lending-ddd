<script>
  let { loan, disabled = false, onReturn } = $props();

  const formatter = new Intl.DateTimeFormat("en-GB", {
    day: "numeric",
    month: "short",
  });

  let dueLabel = $derived(formatter.format(new Date(loan.due_at)));
  let isOverdue = $derived(loan.is_overdue);
</script>

<article class:overdue={isOverdue}>
  <div>
    <strong>{loan.copy_id}</strong>
    <span class="due">
      {isOverdue ? "Was due" : "Due"}
      {dueLabel}
    </span>
  </div>

  <button onclick={onReturn} {disabled}>Return</button>
</article>

<style>
  article {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.7rem 0.9rem;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    background: white;
  }

  article.overdue {
    border-color: #e0a2a2;
    background: #fff7f7;
  }

  strong {
    font-family: monospace;
  }

  .due {
    display: block;
    font-size: 0.82rem;
    color: #6b6b6b;
  }

  article.overdue .due {
    color: #a33;
  }

  button {
    padding: 0.4rem 0.85rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: white;
    font: inherit;
    cursor: pointer;
  }

  button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }
</style>
