document.addEventListener("DOMContentLoaded", () => {

  const search = document.getElementById("job-search");
  const location = document.getElementById("job-location");
  const type = document.getElementById("job-type");
  const jobs = document.querySelectorAll(".job-card");

  function filterJobs(){
    const s = search.value.toLowerCase();
    const l = location.value;
    const t = type.value;

    jobs.forEach(job => {
      const match =
        job.dataset.title.includes(s) &&
        (!l || job.dataset.location === l) &&
        (!t || job.dataset.type === t);

      job.style.display = match ? "flex" : "none";
    });
  }

  [search, location, type].forEach(el =>
    el.addEventListener("input", filterJobs)
  );

});
