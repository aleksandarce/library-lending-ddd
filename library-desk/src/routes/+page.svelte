<script>
  let { data } = $props();
</script>

<h1>Members</h1>

<ul>
  {#each data.members as member (member.id)}
    <li>
      <a href="/members/{member.id}">{member.name}</a>
    </li>
  {/each}
</ul>

<style>
  h1 {
    font-size: 1.3rem;
  }

  ul {
    list-style: none;
    padding: 0;
    display: grid;
    gap: 0.5rem;
  }

  li a {
    display: block;
    padding: 0.75rem 1rem;
    background: white;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
  }

  li a:hover {
    border-color: #bbb;
  }
</style>
