<div class="form-row meeting-form-row meeting-form-wide">
  <label>Titre</label>
  <input type="text" name="title" value="{{ old('title', $meeting?->title) }}" required>
</div>

<div class="form-row meeting-form-row meeting-form-wide">
  <label>Description</label>
  <textarea name="description" rows="4">{{ old('description', $meeting?->description) }}</textarea>
</div>

<div class="grid-3 meeting-form-section">
  <div class="form-row">
    <label>Date</label>
    @php($meetingDateValue = old('meeting_date', optional($meeting?->meeting_date)->format('Y-m-d')))
    <div class="rhs-date-picker" data-rhs-date-picker>
      <input type="hidden" name="meeting_date" value="{{ $meetingDateValue }}" data-rhs-date-value required>
      <button type="button" class="rhs-date-trigger" data-rhs-date-trigger>
        <span data-rhs-date-label>{{ $meetingDateValue ? \Illuminate\Support\Carbon::parse($meetingDateValue)->format('d/m/Y') : 'Choisir une date' }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M8 2v4M16 2v4M4 10h16M6 4h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-date-popover" data-rhs-date-popover hidden>
        <div class="rhs-date-head">
          <button type="button" data-rhs-date-prev aria-label="Mois precedent">
            <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <strong data-rhs-date-month></strong>
          <button type="button" data-rhs-date-next aria-label="Mois suivant">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
        <div class="rhs-date-weekdays">
          <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
        </div>
        <div class="rhs-date-grid" data-rhs-date-grid></div>
      </div>
    </div>
  </div>
  <div class="form-row">
    <label>Heure debut</label>
    @php($startTimeValue = old('start_time', $meeting?->start_time ? substr($meeting->start_time, 0, 5) : ''))
    <div class="rhs-time-picker" data-rhs-time-picker>
      <input type="hidden" name="start_time" value="{{ $startTimeValue }}" data-rhs-time-value required>
      <button type="button" class="rhs-date-trigger" data-rhs-time-trigger>
        <span data-rhs-time-label>{{ $startTimeValue ?: 'Choisir une heure' }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-time-popover" data-rhs-time-popover hidden>
        @for($hour = 0; $hour < 24; $hour++)
          @foreach([0, 15, 30, 45] as $minute)
            @php($timeOption = sprintf('%02d:%02d', $hour, $minute))
            <button type="button" data-rhs-time-option="{{ $timeOption }}" @class(['is-selected' => $startTimeValue === $timeOption])>{{ $timeOption }}</button>
          @endforeach
        @endfor
      </div>
    </div>
  </div>
  <div class="form-row">
    <label>Heure fin</label>
    @php($endTimeValue = old('end_time', $meeting?->end_time ? substr($meeting->end_time, 0, 5) : ''))
    <div class="rhs-time-picker" data-rhs-time-picker>
      <input type="hidden" name="end_time" value="{{ $endTimeValue }}" data-rhs-time-value>
      <button type="button" class="rhs-date-trigger" data-rhs-time-trigger>
        <span data-rhs-time-label>{{ $endTimeValue ?: 'Choisir une heure' }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-time-popover" data-rhs-time-popover hidden>
        <button type="button" data-rhs-time-option="">Aucune heure</button>
        @for($hour = 0; $hour < 24; $hour++)
          @foreach([0, 15, 30, 45] as $minute)
            @php($timeOption = sprintf('%02d:%02d', $hour, $minute))
            <button type="button" data-rhs-time-option="{{ $timeOption }}" @class(['is-selected' => $endTimeValue === $timeOption])>{{ $timeOption }}</button>
          @endforeach
        @endfor
      </div>
    </div>
  </div>
</div>

<div class="grid-2 meeting-form-section">
  <div class="form-row">
    <label>Lieu</label>
    <input type="text" name="location" value="{{ old('location', $meeting?->location) }}" placeholder="Salle, adresse...">
  </div>
  <div class="form-row">
    <label>Lien en ligne</label>
    <input type="url" name="online_link" value="{{ old('online_link', $meeting?->online_link) }}" placeholder="https://...">
  </div>
</div>

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const pad = (value) => String(value).padStart(2, '0');
        const monthLabel = (date) => date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        const dateLabel = (value) => {
          if (!value) return 'Choisir une date';
          const [year, month, day] = value.split('-').map(Number);
          return `${pad(day)}/${pad(month)}/${year}`;
        };

        document.querySelectorAll('[data-rhs-date-picker]').forEach((picker) => {
          const hidden = picker.querySelector('[data-rhs-date-value]');
          const trigger = picker.querySelector('[data-rhs-date-trigger]');
          const label = picker.querySelector('[data-rhs-date-label]');
          const popover = picker.querySelector('[data-rhs-date-popover]');
          const monthNode = picker.querySelector('[data-rhs-date-month]');
          const grid = picker.querySelector('[data-rhs-date-grid]');
          const selectedParts = hidden.value ? hidden.value.split('-').map(Number) : null;
          let cursor = selectedParts ? new Date(selectedParts[0], selectedParts[1] - 1, 1) : new Date();

          const closeOthers = () => {
            document.querySelectorAll('[data-rhs-date-popover], [data-rhs-time-popover]').forEach((node) => {
              if (!picker.contains(node)) node.hidden = true;
            });
          };

          const render = () => {
            const year = cursor.getFullYear();
            const month = cursor.getMonth();
            const firstDay = new Date(year, month, 1);
            const offset = (firstDay.getDay() + 6) % 7;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const todayValue = new Date().toISOString().slice(0, 10);
            monthNode.textContent = monthLabel(cursor);
            grid.innerHTML = '';

            for (let i = 0; i < offset; i++) {
              grid.appendChild(document.createElement('span'));
            }

            for (let day = 1; day <= daysInMonth; day++) {
              const value = `${year}-${pad(month + 1)}-${pad(day)}`;
              const button = document.createElement('button');
              button.type = 'button';
              button.textContent = day;
              button.dataset.date = value;
              if (value === hidden.value) button.classList.add('is-selected');
              if (value === todayValue) button.classList.add('is-today');
              button.addEventListener('click', () => {
                hidden.value = value;
                label.textContent = dateLabel(value);
                popover.hidden = true;
              });
              grid.appendChild(button);
            }
          };

          trigger.addEventListener('click', () => {
            closeOthers();
            popover.hidden = !popover.hidden;
            render();
          });

          picker.querySelector('[data-rhs-date-prev]').addEventListener('click', () => {
            cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
            render();
          });

          picker.querySelector('[data-rhs-date-next]').addEventListener('click', () => {
            cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
            render();
          });

          label.textContent = dateLabel(hidden.value);
        });

        document.querySelectorAll('[data-rhs-time-picker]').forEach((picker) => {
          const hidden = picker.querySelector('[data-rhs-time-value]');
          const trigger = picker.querySelector('[data-rhs-time-trigger]');
          const label = picker.querySelector('[data-rhs-time-label]');
          const popover = picker.querySelector('[data-rhs-time-popover]');

          trigger.addEventListener('click', () => {
            document.querySelectorAll('[data-rhs-date-popover], [data-rhs-time-popover]').forEach((node) => {
              if (!picker.contains(node)) node.hidden = true;
            });
            popover.hidden = !popover.hidden;
          });

          picker.querySelectorAll('[data-rhs-time-option]').forEach((option) => {
            option.addEventListener('click', () => {
              hidden.value = option.dataset.rhsTimeOption;
              label.textContent = option.dataset.rhsTimeOption || 'Choisir une heure';
              picker.querySelectorAll('[data-rhs-time-option]').forEach((node) => node.classList.remove('is-selected'));
              option.classList.add('is-selected');
              popover.hidden = true;
            });
          });
        });

        document.addEventListener('click', (event) => {
          if (event.target.closest('[data-rhs-date-picker], [data-rhs-time-picker]')) return;
          document.querySelectorAll('[data-rhs-date-popover], [data-rhs-time-popover]').forEach((node) => {
            node.hidden = true;
          });
        });
      });
    </script>
  @endpush
@endonce

<div class="grid-2 meeting-form-section">
  <div class="form-row">
    <label>Demande liee</label>
    <select name="recruitment_request_id">
      <option value="">Aucune demande</option>
      @foreach($recruitmentRequests as $requestItem)
        <option value="{{ $requestItem->id }}" @selected((string) old('recruitment_request_id', $meeting?->recruitment_request_id) === (string) $requestItem->id)>
          {{ $requestItem->reference ?: '#'.$requestItem->id }} - {{ $requestItem->position_title }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="form-row">
    <label>Statut</label>
    <select name="status">
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}" @selected(old('status', $meeting?->status ?? \App\Models\Meeting::STATUS_SCHEDULED) === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="form-row meeting-form-row meeting-form-wide">
  <label>Participants</label>
  <div class="permission-grid meeting-participant-grid">
    @foreach($participants as $participant)
      <label class="permission-card">
        <input
          type="checkbox"
          name="participants[]"
          value="{{ $participant->id }}"
          @checked(in_array($participant->id, old('participants', $selectedParticipants ?? [])))
        >
        <span>
          <strong>{{ $participant->name }}</strong>
          <small>{{ $participant->email }} - {{ $participant->role }}</small>
        </span>
      </label>
    @endforeach
  </div>
</div>
